<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Support;

use InvalidArgumentException;
use Threadable\StateMachine\Models\TsmTransitionConfig;

class StateMachineSpecification
{
    /**
     * @return array{
     *   version:int,
     *   stateable_class:string,
     *   states:array<int, array{name:string, initial?:bool, final?:bool}>,
     *   transitions:array<int, array{
     *     name:string,
     *     from:string,
     *     to:string,
     *     trigger:string,
     *     condition_type:string,
     *     conditions?:array<int, array{name:string, ref:string}>,
     *     actions?:array<int, array{name:string, ref:string}>,
     *     validation?:array{rules?:array<string, mixed>, messages?:array<string, string>}
     *   }>
     * }
     */
    public static function load(string $path): array
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("Specification file not found: {$path}");
        }

        $spec = require $path;

        if (! is_array($spec)) {
            throw new InvalidArgumentException('Specification file must return a PHP array.');
        }

        self::validate($spec);

        return $spec;
    }

    /**
     * @param  array<mixed>  $spec
     */
    public static function validate(array $spec): void
    {
        foreach (['version', 'stateable_class', 'states', 'transitions'] as $required) {
            if (! array_key_exists($required, $spec)) {
                throw new InvalidArgumentException("Specification is missing required key [{$required}].");
            }
        }

        if (! is_int($spec['version']) && ! ctype_digit((string) $spec['version'])) {
            throw new InvalidArgumentException('Specification [version] must be an integer.');
        }

        if (! is_string($spec['stateable_class']) || $spec['stateable_class'] === '') {
            throw new InvalidArgumentException('Specification [stateable_class] must be a non-empty string.');
        }

        if (! is_array($spec['states']) || $spec['states'] === []) {
            throw new InvalidArgumentException('Specification [states] must be a non-empty array.');
        }

        if (! is_array($spec['transitions'])) {
            throw new InvalidArgumentException('Specification [transitions] must be an array.');
        }

        $stateNames = [];

        foreach ($spec['states'] as $index => $state) {
            if (! is_array($state) || ! is_string($state['name'] ?? null) || $state['name'] === '') {
                throw new InvalidArgumentException("State at index {$index} must define a non-empty [name].");
            }

            if (in_array($state['name'], $stateNames, true)) {
                throw new InvalidArgumentException("Duplicate state name [{$state['name']}] in specification.");
            }

            $stateNames[] = $state['name'];
        }

        foreach ($spec['transitions'] as $index => $transition) {
            foreach (['name', 'from', 'to', 'trigger', 'condition_type'] as $required) {
                if (! is_string($transition[$required] ?? null) || $transition[$required] === '') {
                    throw new InvalidArgumentException("Transition at index {$index} must define a non-empty [{$required}].");
                }
            }

            if (! in_array($transition['trigger'], [TsmTransitionConfig::TRIGGER_AUTO, TsmTransitionConfig::TRIGGER_COMMAND], true)) {
                throw new InvalidArgumentException("Transition [{$transition['name']}] has an invalid [trigger] value [{$transition['trigger']}].");
            }

            if (! in_array($transition['condition_type'], [
                TsmTransitionConfig::CONDITION_ACTION,
                TsmTransitionConfig::CONDITION_ALWAYS,
                TsmTransitionConfig::CONDITION_OTHERWISE,
            ], true)) {
                throw new InvalidArgumentException("Transition [{$transition['name']}] has an invalid [condition_type] value [{$transition['condition_type']}].");
            }

            if (! in_array($transition['from'], $stateNames, true)) {
                throw new InvalidArgumentException("Transition [{$transition['name']}] references unknown from-state [{$transition['from']}].");
            }

            if (! in_array($transition['to'], $stateNames, true)) {
                throw new InvalidArgumentException("Transition [{$transition['name']}] references unknown to-state [{$transition['to']}].");
            }

            if (isset($transition['conditions'])) {
                self::validateRefList($transition['conditions'], 'conditions', $index);
            }

            if (isset($transition['actions'])) {
                self::validateRefList($transition['actions'], 'actions', $index);
            }

            if (($transition['condition_type'] ?? null) === TsmTransitionConfig::CONDITION_ACTION && empty($transition['conditions'])) {
                throw new InvalidArgumentException("Transition [{$transition['name']}] must define [conditions] when condition_type is ACTION.");
            }
        }
    }

    /**
     * @param  array<mixed>  $items
     */
    protected static function validateRefList(array $items, string $label, int $transitionIndex): void
    {
        foreach ($items as $index => $item) {
            foreach (['name', 'ref'] as $required) {
                if (! is_string($item[$required] ?? null) || $item[$required] === '') {
                    throw new InvalidArgumentException("Transition at index {$transitionIndex} has an invalid {$label} entry at index {$index}; [{$required}] is required.");
                }
            }
        }
    }
}
