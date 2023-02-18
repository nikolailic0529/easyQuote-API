<?php

namespace App\Foundation\Support\Elasticsearch\Policies;

use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SearchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can rebuild search.
     */
    public function rebuildSearch(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }
}
