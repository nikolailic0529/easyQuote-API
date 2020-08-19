<?php

namespace App\Services\Auth\AuthenticatedCases;

use App\Services\Auth\AuthenticatedCase;
use Closure;

class LoggedInDifferentAccount
{
    public function handle(AuthenticatedCase $case, Closure $next)
    {
        if ($case->userRepository->authenticatedIpDoesntExist(
            $case->user->id,
            $case->attempt->ip_address
        )) {
            return $next($case);
        }

        $users = $case->userRepository->findAuthenticatedUsersByIp($case->attempt->ip_address, ['email']);

        $emails = $users->pluck('email')->toArray();

        $case->abort(AU_01, 'AU_01', ['Authenticated-Users' => $emails]);
    }
}
