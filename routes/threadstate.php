<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Threadable\StateMachine\Components\StateMachineRunnerController;

Route::prefix((string) config('threadstate.routes.prefix', 'threadstate'))
    ->middleware((array) config('threadstate.routes.middleware', ['api']))
    ->group(function (): void {
        Route::get('{stateableClass}', [StateMachineRunnerController::class, 'index'])
            ->name('threadstate.index');
        Route::get('{stateableClass}/states', [StateMachineRunnerController::class, 'statesList'])
            ->name('threadstate.states');
        Route::get('{stateableClass}/{uuid}/transitions', [StateMachineRunnerController::class, 'availableTransitionsForObject'])
            ->name('threadstate.transitions.available');
        Route::post('{stateableClass}/{uuid}/transitions/{transitionIdentifier}', [StateMachineRunnerController::class, 'transit'])
            ->name('threadstate.transitions.transit');
        Route::get('{stateableClass}/{uuid}/transitions/{transitionId}/config', [StateMachineRunnerController::class, 'getEffectiveTransitionConfig'])
            ->name('threadstate.transitions.config');
        Route::get('{stateableClass}/{uuid}/logs', [StateMachineRunnerController::class, 'getLogs'])
            ->name('threadstate.logs');
        Route::get('{stateableClass}/transitions/{transitionId}/overrides', [StateMachineRunnerController::class, 'listTransitionOverrides'])
            ->name('threadstate.transitions.overrides.index');
        Route::post('{stateableClass}/transitions/{transitionId}/overrides/{scopeType}/{scopeId}', [StateMachineRunnerController::class, 'saveTransitionOverride'])
            ->name('threadstate.transitions.overrides.store');
        Route::delete('{stateableClass}/transitions/{transitionId}/overrides/{scopeType}/{scopeId}', [StateMachineRunnerController::class, 'deleteTransitionOverride'])
            ->name('threadstate.transitions.overrides.delete');
    });
