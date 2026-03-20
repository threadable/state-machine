<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Threadable\StateMachine\Components\StateMachineRunnerController;

Route::prefix((string) config('state_machine.routes.prefix', 'state-machine'))
    ->middleware((array) config('state_machine.routes.middleware', ['api']))
    ->group(function (): void {
        Route::get('{stateableClass}', [StateMachineRunnerController::class, 'index'])
            ->name('state-machine.index');
        Route::get('{stateableClass}/states', [StateMachineRunnerController::class, 'statesList'])
            ->name('state-machine.states');
        Route::get('{stateableClass}/{uuid}/transitions', [StateMachineRunnerController::class, 'availableTransitionsForObject'])
            ->name('state-machine.transitions.available');
        Route::post('{stateableClass}/{uuid}/transitions/{transitionIdentifier}', [StateMachineRunnerController::class, 'transit'])
            ->name('state-machine.transitions.transit');
        Route::get('{stateableClass}/{uuid}/transitions/{transitionId}/config', [StateMachineRunnerController::class, 'getEffectiveTransitionConfig'])
            ->name('state-machine.transitions.config');
        Route::get('{stateableClass}/{uuid}/logs', [StateMachineRunnerController::class, 'getLogs'])
            ->name('state-machine.logs');
        Route::get('{stateableClass}/transitions/{transitionId}/overrides', [StateMachineRunnerController::class, 'listTransitionOverrides'])
            ->name('state-machine.transitions.overrides.index');
        Route::post('{stateableClass}/transitions/{transitionId}/overrides/{scopeType}/{scopeId}', [StateMachineRunnerController::class, 'saveTransitionOverride'])
            ->name('state-machine.transitions.overrides.store');
        Route::delete('{stateableClass}/transitions/{transitionId}/overrides/{scopeType}/{scopeId}', [StateMachineRunnerController::class, 'deleteTransitionOverride'])
            ->name('state-machine.transitions.overrides.delete');
    });
