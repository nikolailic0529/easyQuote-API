<?php

namespace App\Http\Middleware;

use Closure;

class RefreshPersonalAccessToken
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

        $token = auth()->user()->token();

        if ($token->expires_at->diffInMinutes(now()) < config('auth.tokens.refresh_expire_in')) {
            return $next($request);
        }

        $token->update(['expires_at' => now()->addMinutes(config('auth.tokens.expire'))]);

        return $next($request);
    }
}
