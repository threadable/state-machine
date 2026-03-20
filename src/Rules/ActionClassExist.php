<?php

namespace Threadable\StateMachine\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Threadable\StateMachine\Support\ClassResolver;

class ActionClassExist implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! class_exists(ClassResolver::action((string) $value))) {
            $fail('Action PHP class does not exist');
        }
    }
}
