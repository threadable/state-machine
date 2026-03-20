<?php

namespace Threadable\StateMachine\Exceptions;

use Throwable;

class IllegalStateTransitionException extends \Exception
{
    public function __construct(string $message = '', int $code = 409, ?Throwable $previous = null)
    {
        $this->message = 'This transition is invalid for the current state: '.$message;
        parent::__construct($this->message, $code, $previous);
    }
}
