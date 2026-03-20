<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Support\ClassResolver;

/**
 * Class StateMachineRunner
 */
abstract class StateMachineRunner
{
    public static function transit(Model&StateableModelInterface $obj, string $transition): StateableModelInterface
    {
        return $obj->transit($transition);
    }

    public static function transitByIdentifier(Model&StateableModelInterface $obj, string $transitionIdentifier): StateableModelInterface
    {
        $transition = self::getHumanNameFromSnakeCase($transitionIdentifier);

        return self::transit($obj, $transition);
    }

    public static function getInitialState(Model&StateableModelInterface $obj): TsmState
    {
        return TsmState::query()->where('class', self::getBaseNamespace($obj))->where('initial', 1)->firstOrFail();
    }

    public static function getState(Model&StateableModelInterface $obj): TsmState
    {
        $currentStateId = $obj->getCurrentStateId();

        if ($currentStateId === null) {
            return self::getInitialState($obj);
        }

        return TsmState::query()
            ->where('class', self::getBaseNamespace($obj))
            ->whereKey($currentStateId)
            ->firstOrFail();
    }

    public static function getDestinationState(Model&StateableModelInterface $obj, string $stateName): TsmState
    {
        return TsmState::query()->where('class', self::getBaseNamespace($obj))->where('name', $stateName)->firstOrFail();
    }

    public static function getHumanStateName(TsmState $state): string
    {
        return $state->name;
    }

    public static function getHumanNameFromSnakeCase(string $snakeCaseIdentifier): string
    {
        $camelised = Str::studly($snakeCaseIdentifier);
        $humanState = trim(preg_replace('/([A-Z]+)/', ' $1', $camelised));

        return $humanState;
    }

    /**
     * @return Collection<int, TsmState>
     */
    public static function getStatesList(Model&StateableModelInterface $obj): Collection
    {
        return TsmState::query()->where('class', self::getBaseNamespace($obj))->get();
    }

    /**
     * @return array<string, string>
     */
    public static function getAvailableTransitionsWithIdentifiers(Model&StateableModelInterface $obj): array
    {
        return $obj->getAvailableTransitionsWithIdentifiers();
    }

    /** Namespace helpers */
    public static function getBaseNamespace(Model&StateableModelInterface $obj): string
    {
        $stateClass = explode('\\', get_class($obj));

        return end($stateClass);
    }

    public static function getLogs(Model&StateableModelInterface $obj): Collection
    {
        $query = DB::table('tsm_logs')
            ->select(
                'tsm_logs.created_at',
                'tsm_logs.new_state',
                'tsm_logs.old_state',
                'tsm_logs.transition'
            )
            ->where('stateable_id', $obj->getKey())
            ->where('stateable_class', get_class($obj));

        if (Schema::hasTable('users')) {
            $query
                ->leftJoin('users', 'users.id', '=', 'tsm_logs.users_id')
                ->addSelect('users.email', 'users.first_name', 'users.surname');
        }

        return $query->get();
    }

    public static function resolveActionClass(string $class): string
    {
        return ClassResolver::action($class);
    }

    public static function resolveConditionClass(string $class): string
    {
        return ClassResolver::condition($class);
    }

    public static function resolveModelClass(string $class): string
    {
        return ClassResolver::model($class);
    }
}
