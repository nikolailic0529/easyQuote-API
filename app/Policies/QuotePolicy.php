<?php namespace App\Policies;

use App\Models \ {
    User,
    Quote\Quote
};
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
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
        if($user->can('view_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the quote.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Quote  $quote
     * @return mixed
     */
    public function view(User $user, Quote $quote)
    {
        if($user->can('view_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create quotes.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if($user->can('create_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the quote.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Quote  $quote
     * @return mixed
     */
    public function update(User $user, Quote $quote)
    {
        if($user->can('update_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the quote.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Quote  $quote
     * @return mixed
     */
    public function delete(User $user, Quote $quote)
    {
        if($user->can('delete_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can make copy of the quote.
     *
     * @param User $user
     * @param Quote $quote
     * @return mixed
     */
    public function copy(User $user, Quote $quote)
    {
        return $this->create($user) && $this->view($user, $quote);
    }
}
