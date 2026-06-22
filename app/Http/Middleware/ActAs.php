<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Development-only impersonation. Reads the actor chosen via the navbar switcher
 * (stored in the session) and logs in either a User or a Customer on the matching
 * guard, making that guard the default so `auth()->user()` resolves the actor.
 *
 * Skipped under the console SAPI so feature tests keep their own `actingAs()`
 * identity instead of being clobbered.
 */
class ActAs
{
    /**
     * The actor used before anything has been selected.
     *
     * @var array{type: string, id: int}
     */
    private const DEFAULT_ACTOR = ['type' => 'user', 'id' => 5];

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }

        $actor = $request->session()->get('act_as', self::DEFAULT_ACTOR);

        $guard = ($actor['type'] ?? 'user') === 'customer' ? 'customer' : 'web';

        Auth::shouldUse($guard);
        Auth::guard($guard)->loginUsingId($actor['id']);

        return $next($request);
    }
}
