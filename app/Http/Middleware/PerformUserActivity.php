<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class PerformUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if (false === $user instanceof User) {
            return $next($request);
        }

        /**
         * If the User hasn't recent activity his token will be revoked and User will be logged out.
         */
        if ($user->doesntHaveRecentActivity()) {
            error_abort(LO_00, 'LO_00', 401);
        }

        return $next($request);
    }

    public function terminate($request, $response)
    {
        $user = $request->user();

        if (false === $user instanceof User) {
            return;
        }

        if ($user->doesntHaveRecentActivity()) {
            app('auth.service')->logout($user);
            return;
        }

        $user->freshActivity();
    }
}
