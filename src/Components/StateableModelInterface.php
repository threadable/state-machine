<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;

interface StateableModelInterface
{
    public function getCurrentStateId(): ?int;

    public function getTsmState(): TsmState;

    public function setTsmState(TsmState $state): bool;

    public function transitionExists(string $transition): bool;

    public function getAvailableTransitionsWithIdentifiers(): array;

    public function getAvailableTransitions(): array;

    public function transit(string $transition): self;

    public function validateTransition(TsmTransition $transition): bool;

    public function getValidator(string $transition): StateMachineBaseValidator;

    public function triggerEvent(array $snapshot, TsmTransition $transition): void;

    /**
     * @return array<int, array{type: string, id: int}>
     */
    public function getTransitionOverrideScopes(): array;

    public function getSnapshot(): array;
}
