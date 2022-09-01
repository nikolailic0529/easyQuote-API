<?php

namespace App\Policies;

use App\Models\Data\ExchangeRate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ExchangeRatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Data\ExchangeRate  $exchangeRate
     * @return mixed
     */
    public function view(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Data\ExchangeRate  $exchangeRate
     * @return mixed
     */
    public function update(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Data\ExchangeRate  $exchangeRate
     * @return mixed
     */
    public function delete(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Data\ExchangeRate  $exchangeRate
     * @return mixed
     */
    public function restore(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Data\ExchangeRate  $exchangeRate
     * @return mixed
     */
    public function forceDelete(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can refresh exchange rates.
     *
     * @param  User  $user
     * @return Response
     */
    public function refresh(User $user): Response
    {
        return $user->hasRole(R_SUPER) ? $this->allow() : $this->deny();
    }
}
