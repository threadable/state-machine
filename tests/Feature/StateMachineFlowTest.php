<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Feature;

use App\Models\Order;
use Threadable\StateMachine\Components\StateMachineRunner;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;
use Threadable\StateMachine\Tests\TestCase;

class StateMachineFlowTest extends TestCase
{
    public function test_transition_endpoint_transitions_after_validating_request_data(): void
    {
        $pending = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $approved = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Approved',
            'initial' => 0,
            'final' => 1,
        ]);

        $config = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
            'validation' => [
                'rules' => [
                    'reason' => ['required', 'string'],
                ],
                'messages' => [
                    'reason.required' => 'Reason is required.',
                ],
            ],
        ]);

        TsmTransition::query()->create([
            'name' => 'Approve',
            'from_states_id' => $pending->id,
            'to_states_id' => $approved->id,
            'tsm_transition_configs_id' => $config->id,
        ]);

        $order = Order::query()->create(['name' => 'Endpoint']);

        $response = $this->postJson(route('threadstate.transitions.transit', [
            'stateableClass' => 'Order',
            'uuid' => (string) $order->id,
            'transitionIdentifier' => 'approve',
        ]), [
            'reason' => 'Approved by reviewer',
        ]);

        $response->assertOk();
        $this->assertSame($approved->id, $order->fresh()->tsm_current_state_id);
    }

    public function test_transition_endpoint_rejects_unavailable_transitions(): void
    {
        TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $order = Order::query()->create(['name' => 'Unavailable']);

        $response = $this->postJson(route('threadstate.transitions.transit', [
            'stateableClass' => 'Order',
            'uuid' => (string) $order->id,
            'transitionIdentifier' => 'approve',
        ]));

        $response->assertStatus(409);
    }

    public function test_transition_endpoint_returns_validation_errors_before_transitioning(): void
    {
        $pending = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $approved = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Approved',
            'initial' => 0,
            'final' => 1,
        ]);

        $config = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
            'validation' => [
                'rules' => [
                    'reason' => ['required', 'string'],
                ],
                'messages' => [
                    'reason.required' => 'Reason is required.',
                ],
            ],
        ]);

        TsmTransition::query()->create([
            'name' => 'Approve',
            'from_states_id' => $pending->id,
            'to_states_id' => $approved->id,
            'tsm_transition_configs_id' => $config->id,
        ]);

        $order = Order::query()->create(['name' => 'Needs validation']);

        $response = $this->postJson(route('threadstate.transitions.transit', [
            'stateableClass' => 'Order',
            'uuid' => (string) $order->id,
            'transitionIdentifier' => 'approve',
        ]));

        $response->assertStatus(422);
        $this->assertSame($pending->id, $order->fresh()->tsm_current_state_id);
    }

    public function test_it_sets_initial_state_transitions_and_logs_changes(): void
    {
        $pending = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $approved = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Approved',
            'initial' => 0,
            'final' => 1,
        ]);

        $config = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        TsmTransition::query()->create([
            'name' => 'Approve',
            'from_states_id' => $pending->id,
            'to_states_id' => $approved->id,
            'tsm_transition_configs_id' => $config->id,
        ]);

        $order = Order::query()->create(['name' => 'Example']);

        $this->assertSame($pending->id, $order->fresh()->tsm_current_state_id);
        $this->assertSame('pending', $order->fresh()->tsm_current_state_label);
        $this->assertTrue($order->transitionExists('Approve'));
        $this->assertSame(['approve' => 'Approve'], $order->getAvailableTransitionsWithIdentifiers());

        $order->transit('Approve');

        $this->assertSame($approved->id, $order->fresh()->tsm_current_state_id);
        $this->assertSame('approved', $order->fresh()->tsm_current_state_label);

        $logs = StateMachineRunner::getLogs($order);

        $this->assertCount(1, $logs);
        $this->assertSame('Approve', $logs->first()->transition);
    }

    public function test_it_uses_otherwise_transition_when_no_other_transition_is_valid(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $rejected = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Rejected',
            'initial' => 0,
            'final' => 1,
        ]);

        $fallback = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_OTHERWISE,
        ]);

        TsmTransition::query()->create([
            'name' => 'Reject',
            'from_states_id' => $initial->id,
            'to_states_id' => $rejected->id,
            'tsm_transition_configs_id' => $fallback->id,
        ]);

        $order = Order::query()->create(['name' => 'Fallback']);

        $this->assertSame(['reject' => 'Reject'], $order->getAvailableTransitionsWithIdentifiers());
    }
}
