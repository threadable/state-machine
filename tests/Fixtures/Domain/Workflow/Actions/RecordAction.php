<?php

declare(strict_types=1);

namespace Domain\Workflow\Actions;

use Threadable\StateMachine\Components\StateMachineBaseAction;

class RecordAction extends StateMachineBaseAction
{
    public function execute(): bool
    {
        return true;
    }
}
