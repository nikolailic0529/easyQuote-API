<?php

namespace App\Domain\Authentication\Services\AuthenticatedCases;

use App\Domain\Authentication\Services\AuthenticatedCase;

class AlreadyLoggedIn
{
    public function handle(AuthenticatedCase $case, \Closure $next)
    {
        if ($case->user->isNotAlreadyLoggedIn()) {
            return $next($case);
        }

        if ($case->user->ipMatches($case->attempt->ip)) {
            return $next($case);
        }

        $case->notifyUser();

        $case->abort(AU_00, 'AU_00');
    }
}
