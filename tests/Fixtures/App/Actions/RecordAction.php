<?php

declare(strict_types=1);

namespace App\Actions;

use Threadable\StateMachine\Components\StateMachineBaseAction;

class RecordAction extends StateMachineBaseAction
{
    public function execute(): bool
    {
        return true;
    }
}
