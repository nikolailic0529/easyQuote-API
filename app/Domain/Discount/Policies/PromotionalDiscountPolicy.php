<?php

namespace App\Domain\Discount\Policies;

use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromotionalDiscountPolicy
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

        if ($user->can('view_promo_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, PromotionalDiscount $promotionalDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_promo_discounts')) {
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

        if ($user->can('create_promo_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, PromotionalDiscount $promotionalDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('update_promo_discounts') &&
            $user->getKey() === $promotionalDiscount->{$promotionalDiscount->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, PromotionalDiscount $promotionalDiscount)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('delete_promo_discounts') &&
            $user->getKey() === $promotionalDiscount->{$promotionalDiscount->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }
}
