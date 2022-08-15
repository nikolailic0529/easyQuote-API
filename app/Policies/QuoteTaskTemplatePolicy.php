<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuoteTaskTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function view(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->hasPermissionTo('view_quote_task_template')) {
            return $this->allow();
        }

        return $this->deny(__('access.cant_view', ['item' => 'task template']));
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function update(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->hasPermissionTo('update_quote_task_template')) {
            return $this->allow();
        }

        return $this->deny(__('access.cant_update', ['item' => 'task template']));
    }
}
