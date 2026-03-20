<?php

namespace Threadable\StateMachine\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Threadable\StateMachine\Support\ClassResolver;

class ConditionClassExist implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! class_exists(ClassResolver::condition((string) $value))) {
            $fail('Condition PHP class does not exist');
        }
    }
}
