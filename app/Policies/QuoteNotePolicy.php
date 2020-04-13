<?php

namespace App\Policies;

use App\Models\Quote\QuoteNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteNotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quote notes.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return mixed
     */
    public function view(User $user, QuoteNote $quoteNote)
    {
        //
    }

    /**
     * Determine whether the user can create quote notes.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return mixed
     */
    public function update(User $user, QuoteNote $quoteNote)
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->id === $quoteNote->user_id) {
            return true;
        }

        return $this->deny('You can not update this quote note.');
    }

    /**
     * Determine whether the user can delete the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return mixed
     */
    public function delete(User $user, QuoteNote $quoteNote)
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->id === $quoteNote->user_id) {
            return true;
        }

        return $this->deny('You can not delete this quote note.');
    }

    /**
     * Determine whether the user can restore the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return mixed
     */
    public function restore(User $user, QuoteNote $quoteNote)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\QuoteNote  $quoteNote
     * @return mixed
     */
    public function forceDelete(User $user, QuoteNote $quoteNote)
    {
        //
    }
}
