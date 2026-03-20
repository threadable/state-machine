<?php

namespace Threadable\StateMachine\Components;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Threadable\StateMachine\Exceptions\ValidationException;

abstract class StateMachineBaseValidator
{
    /**
     * Define set of messages for validator
     */
    abstract public function messages(): array;

    /**
     * Define set of rules for validator
     */
    abstract public function rules(): array;

    public function validate(array $data): object
    {
        $validator = Validator::make(
            $data,
            $this->rules(),
            $this->messages()
        );

        return $this->response($validator);
    }

    /**
     * @throws ValidationException
     */
    final protected function response(ValidationValidator $validator): object
    {
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->getMessageBag()->messages() as $key => $message) {
                foreach ($message as $m) {
                    $errors[] = [
                        'field' => $key,
                        'message' => $m,
                    ];
                }
            }

            throw new ValidationException(json_encode($errors));
        }

        return (object) ['valid' => true];
    }
}
