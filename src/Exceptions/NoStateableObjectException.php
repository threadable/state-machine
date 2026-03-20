<?php

namespace Threadable\StateMachine\Exceptions;

class NoStateableObjectException extends \Exception
{
    protected $message = "This object does not have the stateable attribute required by Threadable's State Machine";
}
