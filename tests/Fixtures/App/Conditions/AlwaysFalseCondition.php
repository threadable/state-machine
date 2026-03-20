<?php

declare(strict_types=1);

namespace App\Conditions;

use Threadable\StateMachine\Components\StateMachineBaseCondition;

class AlwaysFalseCondition extends StateMachineBaseCondition
{
    public function execute(): bool
    {
        return false;
    }
}
