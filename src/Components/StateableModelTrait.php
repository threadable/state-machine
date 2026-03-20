<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Threadable\StateMachine\Events\StmStateableObjectStateChangeEvent;
use Threadable\StateMachine\Events\TransitionActionEvent;
use Threadable\StateMachine\Exceptions\IllegalStateTransitionException;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;

/**
 * @phpstan-require-extends Model
 *
 * @phpstan-require-implements StateableModelInterface
 */
trait StateableModelTrait
{
    public function getCurrentStateId(): ?int
    {
        return $this->tsm_current_state_id !== null ? (int) $this->tsm_current_state_id : null;
    }

    /**
     * Checks if there is an instantiated state inside the class. If not, guess the statenamespace from class and
     * instantiates the right one.
     */
    public function getTsmState(): TsmState
    {
        return StateMachineRunner::getState($this);
    }

    public function setTsmState(TsmState $state): bool
    {
        $this->tsm_current_state_id = $state->id;

        return $this->save();
    }

    /**
     * Overrides Eloquent save to set state as Quoting for Deals that still don't have a state
     * but are trying to be persisted
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = [])
    {
        $state = ! $this->tsm_current_state_id
            ? StateMachineRunner::getInitialState($this)
            : TsmState::query()
                ->where('class', StateMachineRunner::getBaseNamespace($this))
                ->whereKey($this->tsm_current_state_id)
                ->firstOrFail();

        $this->tsm_current_state_id = $state->id;

        return parent::save($options);
    }

    /**
     * Get state label
     */
    public function getTsmCurrentStateLabelAttribute(): string
    {
        return Str::snake($this->getTsmState()->name);
    }

    public function transitionExists(string $transition): bool
    {
        return array_key_exists(Str::snake($transition), $this->getAvailableTransitions());
    }

    public function getAvailableTransitions(): array
    {
        $identifiers = [];
        $otherwise = null;

        foreach ($this->getTsmState()->transitions as $transition) {
            $config = $transition->getConfig($this);

            if ($config->condition_type === TsmTransitionConfig::CONDITION_OTHERWISE) {
                $otherwise[Str::snake($transition->name)] = $transition;
            } else {
                if ($this->validateTransition($transition)) {
                    $identifiers[Str::snake($transition->name)] = $transition;
                }
            }
        }

        /**
         * If otherwise transition available and no valid tranistions available
         */
        if (count($identifiers) === 0 && $otherwise) {
            return $otherwise;
        }

        return $identifiers;
    }

    public function getAvailableTransitionsWithIdentifiers(): array
    {
        $identifiers = [];

        foreach ($this->getAvailableTransitions() as $identifier => $transition) {
            $identifiers[$identifier] = $transition->name;
        }

        return $identifiers;
    }

    /**
     * @throws IllegalStateTransitionException
     */
    public function transit(string $transition): self
    {
        $oldStateObj = $this->getSnapshot();
        $state = $this->getTsmState();
        $transitionModel = TsmTransition::query()->where('from_states_id', $state->id)->where('name', $transition)->first();
        if ($transitionModel && $this->validateTransition($transitionModel)) {
            $newState = $transitionModel->to_state;
            $this->setTsmState($newState);
            $this->triggerEvent($oldStateObj, $transitionModel);

            return $this;
        }

        throw new IllegalStateTransitionException($transition);
    }

    public function validateTransition(TsmTransition $transition): bool
    {
        $config = $transition->getConfig($this);

        switch ($config->condition_type) {
            case TsmTransitionConfig::CONDITION_OTHERWISE:
            case TsmTransitionConfig::CONDITION_ALWAYS:
                return true;
            case TsmTransitionConfig::CONDITION_ACTION:
                $rules = [];
                foreach ($config->condition as $condition) {
                    $cond = StateMachineRunner::resolveConditionClass($condition['ref']);
                    $class = new $cond($this);
                    $rules[] = $class->withTransition($transition)->execute();
                }

                return $rules !== [] && ! in_array(false, $rules, true);
            default:
                return false;
        }
    }

    public function getValidator(string $transition): StateMachineBaseValidator
    {
        $state = $this->getTsmState();
        $transitionModel = TsmTransition::query()->where('from_states_id', $state->id)->where('name', $transition)->first();
        $rules = [];
        $messages = [];

        if ($transitionModel) {
            $config = $transitionModel->getConfig($this);
            if ($config->validation) {
                $rules = $config->validation['rules'];
                $messages = $config->validation['messages'] ?? [];
            }
        }

        return new StateMachineValidator(
            $rules,
            $messages
        );
    }

    public function getSnapshot(): array
    {
        return $this->toArray();
    }

    public function triggerEvent(array $snapshot, TsmTransition $transition): void
    {
        event(new TransitionActionEvent($this, $transition));
        // Now our event for the log
        event(new StmStateableObjectStateChangeEvent($this, $transition, true, $snapshot));
    }

    /**
     * @return array<int, array{type: string, id: int}>
     */
    public function getTransitionOverrideScopes(): array
    {
        return [];
    }
}
