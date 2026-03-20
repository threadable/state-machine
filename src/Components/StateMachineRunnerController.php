<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Components;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Threadable\StateMachine\Support\ThreadstateAuthorizer;
use Throwable;

/**
 * Class StateMachineRunnerController
 */
class StateMachineRunnerController extends Controller
{
    protected StateMachineRunnerService $service;

    protected ThreadstateAuthorizer $authorizer;

    /**
     * StateMachineRunnerController constructor.
     */
    public function __construct(StateMachineRunnerService $service, ThreadstateAuthorizer $authorizer)
    {
        $this->service = $service;
        $this->authorizer = $authorizer;
    }

    public function index(Request $request, string $stateableClass): JsonResponse
    {
        $this->authorize($request, 'view_state_machine', [
            'stateable_class' => $stateableClass,
        ]);

        return $this->respond(function () use ($stateableClass) {
            return $this->service->getAllStatesAndTransitions($stateableClass);
        });
    }

    public function statesList(Request $request, string $stateableClass): JsonResponse
    {
        $this->authorize($request, 'view_state_machine', [
            'stateable_class' => $stateableClass,
        ]);

        return $this->respond(function () use ($stateableClass) {
            return $this->service->statesList($stateableClass);
        });
    }

    public function availableTransitionsForObject(Request $request, string $stateableClass, string $uuid): JsonResponse
    {
        $this->authorize($request, 'view_object_transitions', [
            'stateable_class' => $stateableClass,
            'uuid' => $uuid,
        ]);

        return $this->respond(function () use ($stateableClass, $uuid) {
            return $this->service->availableTransitionsForObject($stateableClass, $uuid);
        });
    }

    public function transit(Request $request, string $stateableClass, string $uuid, string $transitionIdentifier): JsonResponse
    {
        $this->authorize($request, 'transit', [
            'stateable_class' => $stateableClass,
            'uuid' => $uuid,
            'transition_identifier' => $transitionIdentifier,
        ]);

        return $this->respond(function () use ($request, $stateableClass, $uuid, $transitionIdentifier) {
            return $this->service->transit($stateableClass, $uuid, $transitionIdentifier, $request->all());
        });
    }

    public function getLogs(Request $request, string $stateableClass, string $uuid): JsonResponse
    {
        $this->authorize($request, 'view_logs', [
            'stateable_class' => $stateableClass,
            'uuid' => $uuid,
        ]);

        return $this->respond(function () use ($stateableClass, $uuid) {
            return $this->service->getLogs($stateableClass, $uuid);
        });
    }

    public function listTransitionOverrides(Request $request, string $stateableClass, string $transitionId): JsonResponse
    {
        $this->authorize($request, 'manage_overrides', [
            'stateable_class' => $stateableClass,
            'transition_id' => (int) $transitionId,
        ]);

        return $this->respond(function () use ($stateableClass, $transitionId) {
            return $this->service->listTransitionOverrides($stateableClass, (int) $transitionId);
        });
    }

    public function getEffectiveTransitionConfig(Request $request, string $stateableClass, string $uuid, string $transitionId): JsonResponse
    {
        $this->authorize($request, 'view_object_transitions', [
            'stateable_class' => $stateableClass,
            'uuid' => $uuid,
            'transition_id' => (int) $transitionId,
        ]);

        return $this->respond(function () use ($stateableClass, $uuid, $transitionId) {
            return $this->service->getEffectiveTransitionConfig($stateableClass, $uuid, (int) $transitionId);
        });
    }

    public function saveTransitionOverride(Request $request, string $stateableClass, string $transitionId, string $scopeType, string $scopeId): JsonResponse
    {
        $this->authorize($request, 'manage_overrides', [
            'stateable_class' => $stateableClass,
            'transition_id' => (int) $transitionId,
            'scope_type' => $scopeType,
            'scope_id' => (int) $scopeId,
        ]);

        return $this->respond(function () use ($stateableClass, $transitionId, $request, $scopeType, $scopeId) {
            return $this->service->saveTransitionOverride($stateableClass, (int) $transitionId, $scopeType, (int) $scopeId, $request->input());
        });
    }

    public function deleteTransitionOverride(Request $request, string $stateableClass, string $transitionId, string $scopeType, string $scopeId): JsonResponse
    {
        $this->authorize($request, 'manage_overrides', [
            'stateable_class' => $stateableClass,
            'transition_id' => (int) $transitionId,
            'scope_type' => $scopeType,
            'scope_id' => (int) $scopeId,
        ]);

        return $this->respond(function () use ($stateableClass, $transitionId, $scopeType, $scopeId) {
            return $this->service->deleteTransitionOverride($stateableClass, (int) $transitionId, $scopeType, (int) $scopeId);
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function authorize(Request $request, string $ability, array $context): void
    {
        $this->authorizer->authorize($request, $ability, $context);
    }

    protected function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getCode() >= 400 ? $exception->getCode() : 500);
        }
    }
}
