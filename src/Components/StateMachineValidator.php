<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

class StateMachineValidator extends StateMachineBaseValidator
{
    /** @var array<string, mixed> */
    protected array $rules;

    /** @var array<string, string> */
    protected array $messages;

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     */
    public function __construct(array $rules, array $messages)
    {
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->messages;
    }
}
