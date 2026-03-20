<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Feature;

use App\Models\Order;
use Threadable\StateMachine\Components\StateMachineRunnerService;
use Threadable\StateMachine\Models\TsmCustomTransition;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;
use Threadable\StateMachine\Tests\TestCase;

class StateMachineRunnerServiceTest extends TestCase
{
    public function test_it_resolves_stateable_objects_via_the_configured_repository_namespace(): void
    {
        TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $order = Order::query()->create(['name' => 'Repository resolved']);

        $service = app(StateMachineRunnerService::class);
        $resolved = $service->getInstanceOfStateableObject('Order', (string) $order->id);

        $this->assertInstanceOf(Order::class, $resolved);
        $this->assertSame($order->id, $resolved->id);
    }

    public function test_it_resolves_transition_overrides_by_scope_precedence(): void
    {
        [$transition, $defaultConfig] = $this->createOrderApprovalTransition();

        $organizationConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
            'action' => [
                ['name' => 'Org action', 'ref' => 'RecordAction'],
            ],
        ]);

        TsmCustomTransition::query()->create([
            'tsm_transitions_id' => $transition->id,
            'tsm_transition_configs_id' => $organizationConfig->id,
            'scope_type' => 'organization',
            'scope_id' => 42,
        ]);

        $orderConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
            'action' => [
                ['name' => 'Order action', 'ref' => 'RecordAction'],
            ],
        ]);

        $order = Order::query()->create([
            'name' => 'Scoped override',
            'organization_id' => 42,
        ]);

        TsmCustomTransition::query()->create([
            'tsm_transitions_id' => $transition->id,
            'tsm_transition_configs_id' => $orderConfig->id,
            'scope_type' => 'order',
            'scope_id' => $order->id,
        ]);

        $this->assertSame($orderConfig->id, $transition->fresh()->getConfig($order)->id);

        TsmCustomTransition::query()
            ->where('scope_type', 'order')
            ->where('scope_id', $order->id)
            ->delete();

        $this->assertSame($organizationConfig->id, $transition->fresh()->getConfig($order)->id);

        TsmCustomTransition::query()
            ->where('scope_type', 'organization')
            ->where('scope_id', 42)
            ->delete();

        $this->assertSame($defaultConfig->id, $transition->fresh()->getConfig($order)->id);
    }

    public function test_it_can_save_and_delete_an_organization_transition_override(): void
    {
        [$transition, $defaultConfig] = $this->createOrderApprovalTransition();

        $service = app(StateMachineRunnerService::class);

        $savedConfig = $service->saveTransitionOverride('Order', $transition->id, 'organization', 42, [
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
            'action' => [
                ['name' => 'Org action', 'ref' => 'RecordAction'],
            ],
        ]);

        $order = Order::query()->create([
            'name' => 'Org override',
            'organization_id' => 42,
        ]);

        $this->assertFalse($savedConfig['is_default']);
        $this->assertSame('organization', $savedConfig['scope_type']);
        $this->assertSame(42, $savedConfig['scope_id']);
        $this->assertSame('RecordAction', $transition->fresh()->getConfig($order)->action[0]['ref']);

        $restoredConfig = $service->deleteTransitionOverride('Order', $transition->id, 'organization', 42);

        $this->assertTrue($restoredConfig['is_default']);
        $this->assertSame($defaultConfig->id, $transition->fresh()->getConfig($order)->id);
        $this->assertSame(1, TsmTransitionConfig::query()->count());
    }

    /**
     * @return array{0: TsmTransition, 1: TsmTransitionConfig}
     */
    protected function createOrderApprovalTransition(): array
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

        $defaultConfig = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_COMMAND,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        $transition = TsmTransition::query()->create([
            'name' => 'Approve',
            'from_states_id' => $pending->id,
            'to_states_id' => $approved->id,
            'tsm_transition_configs_id' => $defaultConfig->id,
        ]);

        return [$transition, $defaultConfig];
    }
}
