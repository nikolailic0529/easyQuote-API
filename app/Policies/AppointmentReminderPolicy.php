<?php

namespace App\Policies;

use App\Models\Appointment\AppointmentReminder;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AppointmentReminderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the appointment reminder.
     *
     * @param  User  $user
     * @param  AppointmentReminder  $reminder
     * @return Response
     */
    public function update(User $user, AppointmentReminder $reminder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($reminder->owner()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->item('reminder')
            ->action('update')
            ->reason('You are not an owner.')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the appointment reminder.
     *
     * @param  User  $user
     * @param  AppointmentReminder  $reminder
     * @return Response
     */
    public function delete(User $user, AppointmentReminder $reminder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($reminder->owner()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->item('reminder')
            ->action('delete')
            ->reason('You are not an owner.')
            ->toResponse();
    }
}
