<?php

namespace App\Domain\Task\Policies;

use App\Domain\Task\Models\TaskReminder;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TaskReminderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the task reminder.
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
