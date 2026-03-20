<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Illuminate\Validation\Rule;
use Threadable\StateMachine\Models\TsmTransitionConfig;
use Threadable\StateMachine\Rules\ActionClassExist;
use Threadable\StateMachine\Rules\ConditionClassExist;

class TransitionConfigValidator extends StateMachineBaseValidator
{
    public function messages(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [
            'trigger' => ['required', Rule::in([TsmTransitionConfig::TRIGGER_AUTO, TsmTransitionConfig::TRIGGER_COMMAND])],
            'condition_type' => ['required', Rule::in([TsmTransitionConfig::CONDITION_ACTION, TsmTransitionConfig::CONDITION_ALWAYS, TsmTransitionConfig::CONDITION_OTHERWISE])],
            'condition' => ['required_if:condition_type,'.TsmTransitionConfig::CONDITION_ACTION, 'array'],
            'condition.*.ref' => ['required_if:condition_type,'.TsmTransitionConfig::CONDITION_ACTION, new ConditionClassExist],
            'condition.*.name' => 'required_if:condition_type,'.TsmTransitionConfig::CONDITION_ACTION,
            'action' => ['nullable', 'array'],
            'action.*.ref' => ['nullable', 'string', new ActionClassExist],
            'action.*.name' => ['nullable', 'string'],
            'validation.rules' => ['required_with:validation', 'array'],
            'validation.rules.*' => ['required', 'array'],
            'validation.rules.*.key' => ['required', 'string', 'distinct'],
            'validation.rules.*.value' => ['required', 'array'],
            'validation.messages.*' => ['filled', 'array'],
            'validation.messages.*.key' => ['string', 'distinct'],
            'validation.messages.*.value' => ['string'],

        ];
    }
}
