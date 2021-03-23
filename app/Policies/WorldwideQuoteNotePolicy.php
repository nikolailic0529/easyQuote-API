<?php

namespace App\Policies;

use App\Models\Quote\QuoteNote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorldwideQuoteNotePolicy
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
        return true;
    }

    /**
     * Determine whether the user can view the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuoteNote  $quoteNote
     * @return mixed
     */
    public function view(User $user, WorldwideQuoteNote $quoteNote)
    {
        return true;
    }

    /**
     * Determine whether the user can create quote notes.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuoteNote  $quoteNote
     * @return mixed
     */
    public function update(User $user, WorldwideQuoteNote $quoteNote)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->getKey() === $quoteNote->{$quoteNote->user()->getForeignKeyName()}) {
            return true;
        }

        return $this->deny('You can not update this quote note.');
    }

    /**
     * Determine whether the user can delete the quote note.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideQuoteNote  $quoteNote
     * @return mixed
     */
    public function delete(User $user, WorldwideQuoteNote $quoteNote)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->getKey() === $quoteNote->{$quoteNote->user()->getForeignKeyName()}) {
            return true;
        }

        return $this->deny('You can not delete this quote note.');
    }
}
