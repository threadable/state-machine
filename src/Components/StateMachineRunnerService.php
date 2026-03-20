<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Threadable\StateMachine\Contracts\StateableRepository;
use Threadable\StateMachine\Exceptions\IllegalStateTransitionException;
use Threadable\StateMachine\Exceptions\InvalidRepositoryException;
use Threadable\StateMachine\Exceptions\NoStateableObjectException;
use Threadable\StateMachine\Exceptions\ValidationException;
use Threadable\StateMachine\Models\TsmCustomTransition;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;
use Threadable\StateMachine\Support\ClassResolver;

/**
 * Class StateMachineRunnerService
 */
class StateMachineRunnerService
{
    public function getAllStatesAndTransitions(string $stateableClass): array
    {
        $stateableObj = $this->newStateableModel($stateableClass);
        $identifiers = [];

        foreach (StateMachineRunner::getStatesList($stateableObj) as $state) {
            $transtions = [];

            foreach ($state->transitions as $transtion) {
                $toState = $transtion->to_state;
                $tranistionConfig = $transtion->getConfig($stateableObj);
                $tranistionConfig['is_default'] = $tranistionConfig->id == $transtion->tsm_transition_configs_id;
                $transtions[] = [
                    'id' => $transtion->id,
                    'name' => $transtion->name,
                    'to_state' => $toState->toArray(),
                    'transition_config' => $tranistionConfig->toArray(),
                ];
            }
            $state_arr = $state->toArray();
            $state_arr['transitions'] = $transtions;
            $identifiers[] = $state_arr;
        }

        return $identifiers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTransitionOverrides(string $stateableClass, int $transitionId): array
    {
        $transition = TsmTransition::query()->findOrFail($transitionId);
        $this->assertTransitionBelongsToStateableClass($transition, $stateableClass);

        return TsmCustomTransition::query()
            ->where('tsm_transitions_id', $transitionId)
            ->orderBy('scope_type')
            ->orderBy('scope_id')
            ->get()
            ->map(function ($override): array {
                $transitionConfig = $override->transitionConfig->toArray();
                $transitionConfig['is_default'] = false;

                return [
                    'scope_type' => $override->scope_type,
                    'scope_id' => $override->scope_id,
                    'transition_config' => $transitionConfig,
                ];
            })
            ->all();
    }

    public function statesList(string $stateableClass): array
    {
        $stateableObj = $this->newStateableModel($stateableClass);
        $identifiers = [];

        foreach (StateMachineRunner::getStatesList($stateableObj) as $state) {
            if ($state->initial || $state->final) {
                continue;
            }
            $identifiers[] = Str::snake($state->name);
        }

        return $identifiers;
    }

    public function availableTransitionsForObject(string $stateableClass, string $uuid): array
    {
        // Service must find the stateable object according to the system rules for finding unique objects
        $stateableObj = $this->getInstanceOfStateableObject($stateableClass, $uuid);

        // Get the list with a nice JSON identifier and a nice human readable
        return StateMachineRunner::getAvailableTransitionsWithIdentifiers($stateableObj);
    }

    public function transit(string $stateableClass, string $uuid, string $transitionIdentifier, array $payload = []): StateableModelInterface
    {
        $stateableObj = $this->getInstanceOfStateableObject($stateableClass, $uuid);

        if (! array_key_exists($transitionIdentifier, $stateableObj->getAvailableTransitionsWithIdentifiers())) {
            throw new IllegalStateTransitionException($transitionIdentifier, 409);
        }

        $validator = $stateableObj->getValidator(StateMachineRunner::getHumanNameFromSnakeCase($transitionIdentifier));
        $validator->validate($payload);

        return StateMachineRunner::transitByIdentifier($stateableObj, $transitionIdentifier);
    }

    public function getInstanceOfStateableObject(string $stateableClass, string $uuid): Model&StateableModelInterface
    {
        $repositoryClass = ClassResolver::repository($stateableClass);
        $repository = app()->make($repositoryClass);

        $repositoryContract = (string) config('state_machine.repositories.contract', StateableRepository::class);

        if (! is_a($repository, $repositoryContract)) {
            throw new InvalidRepositoryException($repositoryClass, $repositoryContract);
        }

        /** @var StateableRepository $repository */
        return $repository->getSingle($uuid);
    }

    public function getLogs(string $stateableClass, string $uuid): Collection
    {
        $stateableObj = $this->getInstanceOfStateableObject($stateableClass, $uuid);

        return StateMachineRunner::getLogs($stateableObj);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEffectiveTransitionConfig(string $stateableClass, string $uuid, int $transitionId): array
    {
        $stateableObject = $this->getInstanceOfStateableObject($stateableClass, $uuid);
        $transition = TsmTransition::query()->findOrFail($transitionId);
        $this->assertTransitionBelongsToStateableClass($transition, $stateableClass);

        $resolvedScope = null;
        $resolvedConfig = null;

        foreach ($stateableObject->getTransitionOverrideScopes() as $scope) {
            $override = TsmCustomTransition::query()
                ->where('tsm_transitions_id', $transitionId)
                ->where('scope_type', $scope['type'])
                ->where('scope_id', $scope['id'])
                ->first();

            if ($override !== null) {
                $resolvedScope = $scope;
                $resolvedConfig = $override->transitionConfig;
                break;
            }
        }

        if ($resolvedConfig === null) {
            $resolvedConfig = $transition->transitionConfig;
        }

        $transitionConfig = $resolvedConfig->toArray();
        $transitionConfig['is_default'] = $resolvedScope === null;
        $transitionConfig['matched_scope'] = $resolvedScope;

        return $transitionConfig;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function prepareTransitionData(array &$data): void
    {
        if (isset($data['validation'])) {
            $rules = [];
            $messages = [];

            foreach ($data['validation']['rules'] as $rule) {
                $rules[$rule['key']] = $rule['value'];
            }

            if (isset($data['validation']['messages'])) {
                foreach ($data['validation']['messages'] as $message) {
                    $messages[$message['key']] = $message['value'];
                }
            }

            $data['validation']['rules'] = $rules;
            $data['validation']['messages'] = $messages;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveTransitionOverride(string $stateableClass, int $id, string $scopeType, int $scopeId, array $data): array
    {
        $transitionConfigValidator = new TransitionConfigValidator;
        $transitionConfigValidator->validate($data);

        $scope = $this->normalizeOverrideScope($scopeType, $scopeId);
        $this->prepareTransitionData($data);

        return DB::transaction(function () use ($id, $data, $scope, $stateableClass) {
            $transition = TsmTransition::query()->findOrFail($id);
            $this->assertTransitionBelongsToStateableClass($transition, $stateableClass);

            if (($data['condition_type'] ?? null) === TsmTransitionConfig::CONDITION_OTHERWISE) {
                $state = $transition->from_state;
                foreach ($state->transitions as $otherTransition) {
                    if ($transition->id === $otherTransition->id) {
                        continue;
                    }

                    if ($otherTransition->getConfigForScopes([$scope])->condition_type === TsmTransitionConfig::CONDITION_OTHERWISE) {
                        throw new Exception('Otherwise transition already exists from this state for the selected scope');
                    }
                }
            }

            $existingOverride = TsmCustomTransition::query()
                ->where('tsm_transitions_id', $id)
                ->where('scope_type', $scope['type'])
                ->where('scope_id', $scope['id'])
                ->first();

            $oldConfigId = $existingOverride?->tsm_transition_configs_id;

            if ($existingOverride !== null) {
                $existingOverride->delete();
            }

            $transitionConfig = new TsmTransitionConfig($data);
            $transitionConfig->save();

            $customTransition = new TsmCustomTransition;
            $customTransition->scope_type = $scope['type'];
            $customTransition->scope_id = $scope['id'];
            $customTransition->tsm_transitions_id = $transition->id;
            $customTransition->tsm_transition_configs_id = $transitionConfig->id;
            $customTransition->save();

            if ($oldConfigId !== null) {
                $this->deleteOrphanedTransitionConfig($oldConfigId);
            }

            $transitionConfigArray = $transitionConfig->toArray();
            $transitionConfigArray['is_default'] = false;
            $transitionConfigArray['scope_type'] = $scope['type'];
            $transitionConfigArray['scope_id'] = $scope['id'];

            return $transitionConfigArray;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteTransitionOverride(string $stateableClass, int $id, string $scopeType, int $scopeId): array
    {
        $scope = $this->normalizeOverrideScope($scopeType, $scopeId);

        return DB::transaction(function () use ($id, $scope, $stateableClass) {
            $transition = TsmTransition::query()->findOrFail($id);
            $this->assertTransitionBelongsToStateableClass($transition, $stateableClass);
            $customTransition = TsmCustomTransition::query()
                ->where('tsm_transitions_id', $id)
                ->where('scope_type', $scope['type'])
                ->where('scope_id', $scope['id'])
                ->first();

            $oldConfigId = $customTransition?->tsm_transition_configs_id;

            if ($customTransition !== null) {
                $customTransition->delete();
            }

            if ($oldConfigId !== null) {
                $this->deleteOrphanedTransitionConfig($oldConfigId);
            }

            $transitionConfigArray = $transition->transitionConfig->toArray();
            $transitionConfigArray['is_default'] = true;
            $transitionConfigArray['scope_type'] = $scope['type'];
            $transitionConfigArray['scope_id'] = $scope['id'];

            return $transitionConfigArray;
        });
    }

    protected function newStateableModel(string $stateableClass): Model&StateableModelInterface
    {
        $resolvedClass = ClassResolver::model(Str::studly($stateableClass));

        $stateableModel = app()->make($resolvedClass);

        if (! $stateableModel instanceof Model || ! $stateableModel instanceof StateableModelInterface) {
            throw new NoStateableObjectException;
        }

        return $stateableModel;
    }

    /**
     * @return array{type: string, id: int}
     */
    protected function normalizeOverrideScope(string $scopeType, int $scopeId): array
    {
        $scopeType = trim($scopeType);

        if ($scopeType === '' || $scopeId < 1) {
            throw new ValidationException('[{"field":"scope","message":"A valid scope_type and scope_id are required."}]');
        }

        return [
            'type' => $scopeType,
            'id' => $scopeId,
        ];
    }

    protected function deleteOrphanedTransitionConfig(int $transitionConfigId): void
    {
        $isDefaultConfig = TsmTransition::query()
            ->where('tsm_transition_configs_id', $transitionConfigId)
            ->exists();

        $isScopedConfig = TsmCustomTransition::query()
            ->where('tsm_transition_configs_id', $transitionConfigId)
            ->exists();

        if (! $isDefaultConfig && ! $isScopedConfig) {
            TsmTransitionConfig::query()->whereKey($transitionConfigId)->delete();
        }
    }

    protected function assertTransitionBelongsToStateableClass(TsmTransition $transition, string $stateableClass): void
    {
        $expectedClass = Str::studly($stateableClass);

        if ($transition->from_state->class !== $expectedClass) {
            throw new ValidationException('[{"field":"transition_id","message":"The transition does not belong to the selected stateable class."}]');
        }
    }
}
