<?php

namespace App\Http\Middleware;

use App\Exceptions\MustChangePasswordException;
use Closure;
use Illuminate\Http\Request;

class EnforceChangePassword
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
        if (!auth()->check() || $this->isAllowedRoute($request)) {
            return $next($request);
        }

        throw_if(auth()->user()->mustChangePassword(), MustChangePasswordException::class);

        return $next($request);
    }

    protected function isAllowedRoute(Request $request)
    {
        $allowed_routes = config('auth.must_change_password.allowed_routes');

        $name = $request->route()->getName();

        return isset(array_flip($allowed_routes)[$name]);
    }
}
