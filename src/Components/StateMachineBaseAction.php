<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Illuminate\Database\Eloquent\Model;

abstract class StateMachineBaseAction
{
    /** @var Model&StateableModelInterface */
    public Model $stateableModel;

    public function __construct(public Model&StateableModelInterface $stateableModelInterface, public ?Model $user)
    {
        $this->stateableModel = $stateableModelInterface;
    }

    public function execute(): bool
    {
        return false;
    }
}
