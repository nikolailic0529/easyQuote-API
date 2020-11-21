<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;

class QuoteTaskTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('view_quote_task_template')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasPermissionTo('update_quote_task_template')) {
            return true;
        }
    }
}
