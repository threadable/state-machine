<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Threadable\StateMachine\Components\StateableModelInterface;
use Threadable\StateMachine\Models\TsmTransition;

class TransitionActionEvent extends AbstractStateChangeEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Model&StateableModelInterface $stateableObject, public TsmTransition $transition)
    {
        parent::__construct($stateableObject);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('post-function');
    }
}
