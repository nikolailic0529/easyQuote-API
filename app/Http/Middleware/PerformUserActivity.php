<?php

namespace App\Http\Middleware;

use Closure;

class PerformUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        /**
         * If the User hasn't recent activity his token will be revoked and User will be logged out.
         */
        if ($user->doesntHaveRecentActivity()) {
            app('auth.service')->logout();

            error_response('LO_00', 401);
        }

        auth()->user()->freshActivity();

        return $next($request);
    }
}
