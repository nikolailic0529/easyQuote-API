<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\User;

class MultiYearDiscountPolicy
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\MultiYearDiscount  $multiYearDiscount
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
     * @param  \App\Models\User  $user
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\MultiYearDiscount  $multiYearDiscount
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\MultiYearDiscount  $multiYearDiscount
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
