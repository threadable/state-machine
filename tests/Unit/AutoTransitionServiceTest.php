<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Unit;

use App\Models\Order;
use Threadable\StateMachine\Components\AutoTransitionService;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;
use Threadable\StateMachine\Tests\TestCase;

class AutoTransitionServiceTest extends TestCase
{
    public function test_it_runs_the_first_available_auto_transition(): void
    {
        $initial = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $next = TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Queued',
            'initial' => 0,
            'final' => 1,
        ]);

        $config = TsmTransitionConfig::query()->create([
            'trigger' => TsmTransitionConfig::TRIGGER_AUTO,
            'condition_type' => TsmTransitionConfig::CONDITION_ALWAYS,
        ]);

        TsmTransition::query()->create([
            'name' => 'Queue',
            'from_states_id' => $initial->id,
            'to_states_id' => $next->id,
            'tsm_transition_configs_id' => $config->id,
        ]);

        $order = Order::query()->create(['name' => 'Auto']);

        $transition = app(AutoTransitionService::class)->run($order);

        $this->assertNotNull($transition);
        $this->assertSame('Queue', $transition->name);
        $this->assertSame($next->id, $order->fresh()->tsm_current_state_id);
        $this->assertSame('queued', $order->fresh()->tsm_current_state_label);
    }

    public function test_it_returns_null_when_no_auto_transition_is_available(): void
    {
        TsmState::query()->create([
            'class' => 'Order',
            'name' => 'Pending',
            'initial' => 1,
            'final' => 0,
        ]);

        $order = Order::query()->create(['name' => 'Manual only']);

        $transition = app(AutoTransitionService::class)->run($order);

        $this->assertNull($transition);
        $this->assertSame('pending', $order->fresh()->tsm_current_state_label);
    }
}
