<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\User;

class PrePayDiscountPolicy
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

        if ($user->can('view_prepay_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\PrePayDiscount  $prePayDiscount
     * @return mixed
     */
    public function view(User $user, PrePayDiscount $prePayDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_prepay_discounts')) {
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

        if ($user->can('create_prepay_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\PrePayDiscount  $prePayDiscount
     * @return mixed
     */
    public function update(User $user, PrePayDiscount $prePayDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('update_prepay_discounts') &&
            $user->getKey() === $prePayDiscount->{$prePayDiscount->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\PrePayDiscount  $prePayDiscount
     * @return mixed
     */
    public function delete(User $user, PrePayDiscount $prePayDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('delete_prepay_discounts') &&
            $user->getKey() === $prePayDiscount->{$prePayDiscount->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }
}
