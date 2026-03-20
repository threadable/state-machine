<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Exceptions;

use Throwable;

class InvalidRepositoryException extends \Exception
{
    public function __construct(string $repositoryClass, string $contract, int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct(
            "Repository [{$repositoryClass}] must implement [{$contract}].",
            $code,
            $previous
        );
    }
}
