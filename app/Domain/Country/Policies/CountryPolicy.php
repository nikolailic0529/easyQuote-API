<?php

namespace App\Domain\Country\Policies;

use App\Domain\Country\Models\Country;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CountryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any countries.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_countries');
    }

    /**
     * Determine whether the user can view the country.
     *
     * @return mixed
     */
    public function view(User $user, Country $country)
    {
        return $user->can('view_countries');
    }

    /**
     * Determine whether the user can create countries.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('create_countries');
    }

    /**
     * Determine whether the user can update the country.
     *
     * @return mixed
     */
    public function update(User $user, Country $country)
    {
        if ($country->isSystem()) {
            return $this->deny(CSU_01);
        }

        return $user->can('update_countries');
    }

    /**
     * Determine whether the user can delete the country.
     *
     * @return mixed
     */
    public function delete(User $user, Country $country)
    {
        if ($country->isSystem()) {
            return $this->deny(CSD_01);
        }

        return $user->can('delete_countries');
    }
}
