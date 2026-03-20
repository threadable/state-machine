<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Unit;

use InvalidArgumentException;
use Threadable\StateMachine\Support\StateMachineSpecification;
use Threadable\StateMachine\Tests\TestCase;

class StateMachineSpecificationTest extends TestCase
{
    public function test_it_validates_a_well_formed_specification(): void
    {
        $spec = [
            'version' => 1,
            'stateable_class' => 'Order',
            'states' => [
                ['name' => 'Initial', 'initial' => true],
                ['name' => 'Queued', 'final' => true],
            ],
            'transitions' => [
                [
                    'name' => 'Queue',
                    'from' => 'Initial',
                    'to' => 'Queued',
                    'trigger' => 'AUTO',
                    'condition_type' => 'ACTION',
                    'conditions' => [
                        [
                            'name' => 'Ready',
                            'ref' => 'AlwaysTrueCondition',
                        ],
                    ],
                ],
            ],
        ];

        StateMachineSpecification::validate($spec);

        $this->addToAssertionCount(1);
    }

    public function test_it_rejects_duplicate_state_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate state name [Initial]');

        StateMachineSpecification::validate([
            'version' => 1,
            'stateable_class' => 'Order',
            'states' => [
                ['name' => 'Initial'],
                ['name' => 'Initial'],
            ],
            'transitions' => [],
        ]);
    }

    public function test_it_rejects_unknown_transition_states(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('references unknown to-state [Missing]');

        StateMachineSpecification::validate([
            'version' => 1,
            'stateable_class' => 'Order',
            'states' => [
                ['name' => 'Initial'],
            ],
            'transitions' => [
                [
                    'name' => 'Queue',
                    'from' => 'Initial',
                    'to' => 'Missing',
                    'trigger' => 'AUTO',
                    'condition_type' => 'ALWAYS',
                ],
            ],
        ]);
    }

    public function test_it_requires_conditions_for_action_condition_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must define [conditions] when condition_type is ACTION');

        StateMachineSpecification::validate([
            'version' => 1,
            'stateable_class' => 'Order',
            'states' => [
                ['name' => 'Initial'],
                ['name' => 'Queued'],
            ],
            'transitions' => [
                [
                    'name' => 'Queue',
                    'from' => 'Initial',
                    'to' => 'Queued',
                    'trigger' => 'AUTO',
                    'condition_type' => 'ACTION',
                ],
            ],
        ]);
    }

    public function test_it_rejects_invalid_trigger_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has an invalid [trigger] value [TIMER]');

        StateMachineSpecification::validate([
            'version' => 1,
            'stateable_class' => 'Order',
            'states' => [
                ['name' => 'Initial'],
                ['name' => 'Queued'],
            ],
            'transitions' => [
                [
                    'name' => 'Queue',
                    'from' => 'Initial',
                    'to' => 'Queued',
                    'trigger' => 'TIMER',
                    'condition_type' => 'ALWAYS',
                ],
            ],
        ]);
    }
}
