<?php namespace App\Policies;

use App\Models \ {
    User,
    Quote\Margin\Margin
};
use Illuminate\Auth\Access\HandlesAuthorization;

class MarginPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any margins.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the margin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Margin\Margin  $margin
     * @return mixed
     */
    public function view(User $user, Margin $margin)
    {
        return $user->collaboration_id === $margin->collaboration_id;
    }

    /**
     * Determine whether the user can create margins.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if($user->can('create_margins')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the margin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Margin\Margin  $margin
     * @return mixed
     */
    public function update(User $user, Margin $margin)
    {
        if($user->can('update_collaboration_margins')) {
            return $user->collaboration_id === $margin->collaboration_id;
        }

        if($user->can('update_own_margins')) {
            return $user->id === $margin->user_id;
        }
    }

    /**
     * Determine whether the user can delete the margin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Margin\Margin  $margin
     * @return mixed
     */
    public function delete(User $user, Margin $margin)
    {
        if($user->can('delete_collaboration_margins')) {
            return $user->collaboration_id === $margin->collaboration_id;
        }

        if($user->can('delete_own_margins')) {
            return $user->id === $margin->user_id;
        }
    }
}
