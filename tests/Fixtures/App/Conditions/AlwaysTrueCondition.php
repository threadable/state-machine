<?php

declare(strict_types=1);

namespace App\Conditions;

use Threadable\StateMachine\Components\StateMachineBaseCondition;

class AlwaysTrueCondition extends StateMachineBaseCondition
{
    public function execute(): bool
    {
        return true;
    }
}
