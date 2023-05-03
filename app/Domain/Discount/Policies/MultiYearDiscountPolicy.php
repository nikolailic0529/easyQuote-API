<?php

namespace App\Domain\Discount\Policies;

use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MultiYearDiscountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_multiyear_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, MultiYearDiscount $multiYearDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_multiyear_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_multiyear_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, MultiYearDiscount $multiYearDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('update_multiyear_discounts') &&
            $user->getKey() === $multiYearDiscount->{$multiYearDiscount->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, MultiYearDiscount $multiYearDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('delete_multiyear_discounts') &&
            $user->getKey() === $multiYearDiscount->{$multiYearDiscount->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }
}
