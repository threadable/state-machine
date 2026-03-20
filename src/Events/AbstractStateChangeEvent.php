<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Threadable\StateMachine\Components\StateableModelInterface;

/**
 * AbstractState
 *
 * This abstract class implements the required methods used on a State to know what transition is being called,
 * if the transition exists, if the validations are ran fine and then triggers the required events for this
 */
abstract class AbstractStateChangeEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?Authenticatable $user;

    public Carbon $dateTime;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Model&StateableModelInterface $stateableObject)
    {
        $this->user = Auth::user();
        $this->dateTime = Carbon::now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('channel-name');
    }
}
