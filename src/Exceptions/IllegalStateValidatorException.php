<?php

namespace Threadable\StateMachine\Exceptions;

class IllegalStateValidatorException extends \Exception
{
    protected $message = "Validator must be instance of Threadable\StateMachine\Components\StateMachineBaseValidator";
}
