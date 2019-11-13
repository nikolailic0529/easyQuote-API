<?php

namespace App\Policies;

use App\Models\{
    User,
    Quote\Discount\Discount
};
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any discounts.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->can('view_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the discount.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount  $discoun\Discountt
     * @return mixed
     */
    public function view(User $user, Discount $discount)
    {
        if ($user->can('view_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create discounts.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->can('create_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the discount.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount  $discoun\Discountt
     * @return mixed
     */
    public function update(User $user, Discount $discount)
    {
        if ($user->can('update_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the discount.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount  $discoun\Discountt
     * @return mixed
     */
    public function delete(User $user, Discount $discount)
    {
        if ($user->can('delete_discounts')) {
            return true;
        }
    }
}
