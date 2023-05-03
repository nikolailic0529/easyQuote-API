<?php

namespace App\Foundation\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

final class SetAppLocale
{
    public function __construct(
        protected readonly Application $application,
    ) {
    }

    public function handle(Request $request, \Closure $next)
    {
        if ($locale = $request->header('X-User-Lang')) {
            $this->application->setLocale($locale);
        }

        return $next($request);
    }
}
