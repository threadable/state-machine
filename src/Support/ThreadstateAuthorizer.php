<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class ThreadstateAuthorizer
{
    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     */
    public function authorize(Request $request, string $ability, array $context = []): void
    {
        $callback = config("threadstate.authorization.{$ability}");

        if (! is_callable($callback)) {
            return;
        }

        $allowed = (bool) $callback($request->user(), $request, $context);

        if (! $allowed) {
            throw new AuthorizationException("Unauthorized to perform [{$ability}].");
        }
    }
}
