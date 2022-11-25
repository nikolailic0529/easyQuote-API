<?php

namespace App\Policies;

use App\Models\Task\TaskReminder;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TaskReminderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the task reminder.
     *
     * @param  User  $user
     * @param  TaskReminder  $reminder
     * @return Response
     */
    public function update(User $user, TaskReminder $reminder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($reminder->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->item('reminder')
            ->action('update')
            ->reason('You are not an owner.')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the task reminder.
     *
     * @param  User  $user
     * @param  TaskReminder  $reminder
     * @return Response
     */
    public function delete(User $user, TaskReminder $reminder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($reminder->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->item('reminder')
            ->action('delete')
            ->reason('You are not an owner.')
            ->toResponse();
    }
}
