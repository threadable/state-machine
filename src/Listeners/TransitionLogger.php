<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Threadable\StateMachine\Events\StmStateableObjectStateChangeEvent;

class TransitionLogger
{
    /**
     * Handle the event.
     */
    public function handle(StmStateableObjectStateChangeEvent $event): void
    {
        $user = $event->user;

        DB::table('tsm_logs')->insert([
            'users_id' => ($user instanceof Model) ? $user->getKey() : null,
            'stateable_id' => $event->stateableObject->getKey(),
            'stateable_class' => get_class($event->stateableObject),
            'stateable_data' => json_encode($event->snapshot),
            'old_state' => $event->transition->from_state->name,
            'new_state' => $event->transition->to_state->name,
            'transition' => $event->transition->name,
            'result' => $event->result,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
