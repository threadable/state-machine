<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Illuminate\Database\Eloquent\Model;
use Threadable\StateMachine\Models\TsmTransition;

abstract class StateMachineBaseCondition
{
    /** @var Model&StateableModelInterface */
    public Model $stateableModel;

    protected TsmTransition $tsmTransitionObject;

    public function __construct(Model&StateableModelInterface $stateableModelInterface)
    {
        $this->stateableModel = $stateableModelInterface;
    }

    public function execute(): bool
    {
        return false;
    }

    public function message(): string
    {
        return 'Not Implemented';
    }

    public function withTransition(TsmTransition $transition): self
    {
        $this->tsmTransitionObject = $transition;

        return $this;
    }
}
