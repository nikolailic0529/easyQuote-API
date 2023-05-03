<?php

namespace App\Domain\Appointment\Policies;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Appointment $appointment)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Appointment $appointment)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($appointment->owner()->is($user)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Appointment $appointment)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($appointment->owner()->is($user)) {
            return true;
        }

        return false;
    }
}
