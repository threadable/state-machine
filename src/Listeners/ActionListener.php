<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Listeners;

use Illuminate\Database\Eloquent\Model;
use Threadable\StateMachine\Components\StateMachineBaseAction;
use Threadable\StateMachine\Components\StateMachineRunner;
use Threadable\StateMachine\Events\TransitionActionEvent;

class ActionListener
{
    /**
     * Handle the event.
     */
    public function handle(TransitionActionEvent $event): void
    {

        $user = $event->user;
        $stateableObject = $event->stateableObject;
        $transition = $event->transition;
        $config = $transition->getConfig($stateableObject);
        $actions = $config->action;

        if ($actions) {
            foreach ($actions as $action) {
                $actionClass = StateMachineRunner::resolveActionClass($action['ref']);
                /** @var StateMachineBaseAction $class */
                $class = new $actionClass($stateableObject, $user instanceof Model ? $user : null);
                $class->execute();
            }
        }
    }
}
