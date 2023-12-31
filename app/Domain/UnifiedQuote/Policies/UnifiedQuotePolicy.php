<?php

namespace App\Domain\UnifiedQuote\Policies;

use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnifiedQuotePolicy
{
    use HandlesAuthorization;

    public function viewEntitiesOfAnyBusinessDivision(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasAnyPermission(['view_own_quotes', 'view_own_ww_quotes'])) {
            return true;
        }
    }

    public function viewEntitiesOfAnyUser(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }
}
