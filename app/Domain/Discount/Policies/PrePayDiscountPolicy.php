<?php

namespace App\Domain\Discount\Policies;

use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrePayDiscountPolicy
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

        if ($user->can('view_prepay_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
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
