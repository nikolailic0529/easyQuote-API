<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SearchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can rebuild search.
     *
     * @param  User  $user
     * @return Response
     */
    public function rebuildSearch(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }
}
