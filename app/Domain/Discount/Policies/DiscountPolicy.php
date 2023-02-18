<?php

namespace App\Domain\Discount\Policies;

use App\Domain\Discount\Models\Discount;
use App\Domain\User\Models\{
    User
};
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any discounts.
     *
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User       $user
     * @param \App\Domain\Rescue\Models\Discount $discoun\Discountt
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User       $user
     * @param \App\Domain\Rescue\Models\Discount $discoun\Discountt
     *
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
     * @param \App\Domain\User\Models\User       $user
     * @param \App\Domain\Rescue\Models\Discount $discoun\Discountt
     *
     * @return mixed
     */
    public function delete(User $user, Discount $discount)
    {
        if ($user->can('delete_discounts')) {
            return true;
        }
    }
}
