<?php

namespace App\Domain\ExchangeRate\Policies;

use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ExchangeRatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, ExchangeRate $exchangeRate)
    {
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, ExchangeRate $exchangeRate)
    {
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, ExchangeRate $exchangeRate)
    {
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, ExchangeRate $exchangeRate)
    {
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, ExchangeRate $exchangeRate)
    {
    }

    /**
     * Determine whether the user can refresh exchange rates.
     */
    public function refresh(User $user): Response
    {
        return $user->hasRole(R_SUPER) ? $this->allow() : $this->deny();
    }
}
