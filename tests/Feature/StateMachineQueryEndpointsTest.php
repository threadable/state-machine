<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Feature;

use App\Models\Order;
use Threadable\StateMachine\Models\TsmCustomTransition;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;
use Threadable\StateMachine\Tests\TestCase;

class StateMachineQueryEndpointsTest extends TestCase
{
    public function test_it_lists_states_and_transitions_for_a_state_machine(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $next = TsmState::query()->create([
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
            'from_states_id' => $initial->id,
            'to_states_id' => $next->id,
            'tsm_transition_configs_id' => $config->id,
        ]);

        $response = $this->getJson('/state-machine/Order');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Pending']);
        $response->assertJsonFragment(['name' => 'Approve']);
    }

    public function test_it_lists_states_for_a_state_machine(): void
    {
        $pending = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 0,
            'final' => 0,
        ]);

        TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Initial',
            'initial' => 1,
            'final' => 0,
        ]);

        TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Approved',
            'initial' => 0,
            'final' => 1,
        ]);

        $response = $this->getJson('/state-machine/Order/states');

        $response->assertOk()->assertExactJson(['pending']);
        $this->assertSame('Pending', $pending->name);
    }

    public function test_it_lists_available_transitions_for_an_object(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $next = TsmState::query()->create([
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
            'from_states_id' => $initial->id,
            'to_states_id' => $next->id,
            'tsm_transition_configs_id' => $config->id,
        ]);

        $order = Order::query()->create(['name' => 'Endpoint']);

        $response = $this->getJson("/state-machine/Order/{$order->id}/transitions");

        $response->assertOk()->assertExactJson([
            'approve' => 'Approve',
        ]);
    }

    public function test_it_returns_logs_for_an_object(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $next = TsmState::query()->create([
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
            'from_states_id' => $initial->id,
            'to_states_id' => $next->id,
            'tsm_transition_configs_id' => $config->id,
        ]);

        $order = Order::query()->create(['name' => 'Logs']);
        $order->transit('Approve');

        $response = $this->getJson("/state-machine/Order/{$order->id}/logs");

        $response->assertOk();
        $response->assertJsonFragment(['transition' => 'Approve']);
        $response->assertJsonFragment(['new_state' => 'Approved']);
    }

    public function test_it_lists_transition_overrides_for_a_transition(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $next = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Approved',
            'initial' => 0,
            'final' => 1,
        ]);

        $defaultConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        $transition = TsmTransition::query()->create([
            'name' => 'Approve',
            'from_states_id' => $initial->id,
            'to_states_id' => $next->id,
            'tsm_transition_configs_id' => $defaultConfig->id,
        ]);

        $overrideConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        TsmCustomTransition::query()->create([
            'tsm_transitions_id' => $transition->id,
            'tsm_transition_configs_id' => $overrideConfig->id,
            'scope_type' => 'organization',
            'scope_id' => 42,
        ]);

        $response = $this->getJson(route('state-machine.transitions.overrides.index', [
            'stateableClass' => 'Order',
            'transitionId' => (string) $transition->id,
        ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'scope_type' => 'organization',
            'scope_id' => 42,
        ]);
    }

    public function test_it_returns_the_effective_transition_config_for_an_object(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $next = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Approved',
            'initial' => 0,
            'final' => 1,
        ]);

        $defaultConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        $transition = TsmTransition::query()->create([
            'name' => 'Approve',
            'from_states_id' => $initial->id,
            'to_states_id' => $next->id,
            'tsm_transition_configs_id' => $defaultConfig->id,
        ]);

        $overrideConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        $order = Order::query()->create([
            'name' => 'Config endpoint',
            'organization_id' => 42,
        ]);

        TsmCustomTransition::query()->create([
            'tsm_transitions_id' => $transition->id,
            'tsm_transition_configs_id' => $overrideConfig->id,
            'scope_type' => 'organization',
            'scope_id' => 42,
        ]);

        $response = $this->getJson(route('state-machine.transitions.config', [
            'stateableClass' => 'Order',
            'uuid' => (string) $order->id,
            'transitionId' => (string) $transition->id,
        ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'is_default' => false,
            'matched_scope' => [
                'type' => 'organization',
                'id' => 42,
            ],
        ]);
    }

    public function test_it_applies_authorization_callbacks_to_package_routes(): void
    {
        config()->set('state_machine.authorization.view_state_machine', static fn ($user, $request, array $context): bool => false);

        $response = $this->getJson('/state-machine/Order');

        $response->assertStatus(403);
    }

    public function test_it_can_store_and_delete_transition_overrides_via_http(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $next = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Approved',
            'initial' => 0,
            'final' => 1,
        ]);

        $defaultConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        $transition = TsmTransition::query()->create([
            'name' => 'Approve',
            'from_states_id' => $initial->id,
            'to_states_id' => $next->id,
            'tsm_transition_configs_id' => $defaultConfig->id,
        ]);

        $storeResponse = $this->postJson(route('state-machine.transitions.overrides.store', [
            'stateableClass' => 'Order',
            'transitionId' => (string) $transition->id,
            'scopeType' => 'organization',
            'scopeId' => '42',
        ]), [
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        $storeResponse->assertOk()->assertJsonFragment([
            'scope_type' => 'organization',
            'scope_id' => 42,
            'is_default' => false,
        ]);

        $deleteResponse = $this->deleteJson(route('state-machine.transitions.overrides.delete', [
            'stateableClass' => 'Order',
            'transitionId' => (string) $transition->id,
            'scopeType' => 'organization',
            'scopeId' => '42',
        ]));

        $deleteResponse->assertOk()->assertJsonFragment([
            'scope_type' => 'organization',
            'scope_id' => 42,
            'is_default' => true,
        ]);
    }
}
