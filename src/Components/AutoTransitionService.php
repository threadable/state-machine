<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Illuminate\Support\Arr;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;

class AutoTransitionService
{
    public function run(StateableModelInterface $stateableModel): ?TsmTransition
    {
        $autoTransitions = [];

        foreach ($stateableModel->getAvailableTransitions() as $availableTransition) {
            $config = $availableTransition->getConfig($stateableModel);

            if ($config->trigger === TsmTransitionConfig::TRIGGER_AUTO) {
                $autoTransitions[] = $availableTransition;
            }
        }

        /** @var TsmTransition|null $transition */
        $transition = Arr::first($autoTransitions);

        if ($transition === null) {
            return null;
        }

        $stateableModel->transit($transition->name);

        return $transition;
    }
}
