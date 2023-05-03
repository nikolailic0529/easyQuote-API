<?php

namespace App\Domain\Template\Policies;

use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class QuoteTaskTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->hasPermissionTo('view_quote_task_template')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('task template')
            ->toResponse();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function update(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->hasPermissionTo('update_quote_task_template')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('task template')
            ->toResponse();
    }
}
