<?php

namespace App\Policies;

use App\Models\{
    User,
    Quote\Quote
};
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quotes.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_quotes') || $user->can('view_own_quotes');
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
        if ($user->can('view_quotes')) {
            return true;
        }

        if ($user->can('view_own_quotes')) {
            return $user->id === $quote->user_id;
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
        return $user->can('create_quotes');
    }

    /**
     * Determine whether the user can update the quote state.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Quote  $quote
     * @return mixed
     */
    public function state(User $user, Quote $quote)
    {
        if ($quote->isSubmitted()) {
            return $this->deny(QSU_01);
        }

        if ($user->can('update_quotes')) {
            return true;
        }

        if ($user->can('update_own_quotes')) {
            return $user->id === $quote->user_id;
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
        if ($user->can('update_quotes')) {
            return true;
        }

        if ($user->can('update_own_quotes')) {
            return $user->id === $quote->user_id;
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
        if ($user->can('delete_quotes')) {
            return true;
        }

        if ($user->can('delete_own_quotes')) {
            return $user->id === $quote->user_id;
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

    /**
     * Determine whether the user can download the generated quote pdf.
     *
     * @param User $user
     * @param Quote $quote
     * @return mixed
     */
    public function download_pdf(User $user, Quote $quote)
    {
        return $user->can('download_quote_pdf') && $this->view($user, $quote);
    }
}
