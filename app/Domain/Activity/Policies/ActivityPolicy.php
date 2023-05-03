<?php

namespace App\Domain\Activity\Policies;

use App\Domain\Activity\Models\Activity;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activities.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_activities')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('audit')
            ->toResponse();
    }

    /**
     * Determine whether the user can view the activity.
     */
    public function view(User $user, Activity $activity): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_activities')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('audit')
            ->toResponse();
    }
}
