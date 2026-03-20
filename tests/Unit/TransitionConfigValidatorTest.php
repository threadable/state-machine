<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Unit;

use Threadable\StateMachine\Components\TransitionConfigValidator;
use Threadable\StateMachine\Exceptions\ValidationException;
use Threadable\StateMachine\Models\TsmTransitionConfig;
use Threadable\StateMachine\Tests\TestCase;

class TransitionConfigValidatorTest extends TestCase
{
    public function test_it_accepts_a_valid_action_condition_payload(): void
    {
        $validator = new TransitionConfigValidator;

        $result = $validator->validate([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ACTION,
            'condition' => [
                [
                    'name' => 'Always true',
                    'ref' => 'AlwaysTrueCondition',
                ],
            ],
            'action' => [
                [
                    'name' => 'Record action',
                    'ref' => 'RecordAction',
                ],
            ],
        ]);

        $this->assertTrue($result->valid);
    }

    public function test_it_rejects_expression_condition_type(): void
    {
        $this->expectException(ValidationException::class);

        (new TransitionConfigValidator)->validate([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => 'EXPRESSION',
        ]);
    }

    public function test_it_uses_configured_action_and_condition_namespaces(): void
    {
        config()->set('threadstate.namespaces.conditions', 'Domain\\Workflow\\Conditions\\');
        config()->set('threadstate.namespaces.actions', 'Domain\\Workflow\\Actions\\');

        $result = (new TransitionConfigValidator)->validate([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ACTION,
            'condition' => [
                [
                    'name' => 'Always true',
                    'ref' => 'AlwaysTrueCondition',
                ],
            ],
            'action' => [
                [
                    'name' => 'Record action',
                    'ref' => 'RecordAction',
                ],
            ],
        ]);

        $this->assertTrue($result->valid);
    }
}
