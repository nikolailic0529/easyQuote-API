<?php

namespace App\Http\Middleware;

use App\Exceptions\MustChangePasswordException;
use Closure;

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
        if (!auth()->check() || $request->route()->getName() === 'profile.update') {
            return $next($request);
        }

        throw_if(auth()->user()->mustChangePassword(), MustChangePasswordException::class);

        return $next($request);
    }
}
