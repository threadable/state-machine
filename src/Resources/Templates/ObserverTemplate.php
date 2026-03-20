<?php

namespace __OBSERVER_NAMESPACE__;

use Threadable\StateMachine\Components\AutoTransitionService;
use Threadable\StateMachine\Components\StateableModelInterface;

class __STATEABLE_CLASS__
{
    public function __construct(protected AutoTransitionService $autoTransitionService) {}

    /**
     * Handle the post "created" event.
     */
    public function created(StateableModelInterface $stateableModel): void
    {
        $this->autoTransitionService->run($stateableModel);
    }

    /**
     * Handle the post "updated" event.
     */
    public function updated(StateableModelInterface $stateableModel): void
    {
        $this->autoTransitionService->run($stateableModel);
    }

    /**
     * Handle the post "deleted" event.
     */
    public function deleted(StateableModelInterface $stateableModel): void
    {
        //
    }

    /**
     * Handle the post "restored" event.
     */
    public function restored(StateableModelInterface $stateableModel): void
    {
        //
    }

    /**
     * Handle the post "force deleted" event.
     */
    public function forceDeleted(StateableModelInterface $stateableModel): void
    {
        //
    }
}
