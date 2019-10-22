<?php namespace App\Policies;

use App\Models \ {
    User,
    QuoteFile\QuoteFile
};
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteFilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quote files.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the quote file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\QuoteFile  $quoteFile
     * @return mixed
     */
    public function view(User $user, QuoteFile $quoteFile)
    {
        return $user->collaboration_id === $quoteFile->collaboration_id;
    }

    /**
     * Determine whether the user can create quote files.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if($user->can('create_quote_files')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the quote file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\QuoteFile  $quoteFile
     * @return mixed
     */
    public function update(User $user, QuoteFile $quoteFile)
    {
        if($user->can('update_collaboration_quote_files')) {
            return $user->collaboration_id === $quoteFile->collaboration_id;
        }

        if($user->can('update_own_quote_files')) {
            return $user->id === $quoteFile->user_id;
        }
    }

    /**
     * Determine whether the user can delete the quote file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteFile\QuoteFile  $quoteFile
     * @return mixed
     */
    public function delete(User $user, QuoteFile $quoteFile)
    {
        if($user->can('delete_collaboration_quote_files')) {
            return $user->collaboration_id === $quoteFile->collaboration_id;
        }

        if($user->can('delete_own_quote_files')) {
            return $user->id === $quoteFile->user_id;
        }
    }

    /**
     * Determine whether the user can handle the quote file.
     *
     * @param \App\Models\User $user
     * @param \App\Models\QuoteFile\QuoteFile $quoteFile
     * @return mixed
     */
    public function handle(User $user, QuoteFile $quoteFile)
    {
        if($user->can('handle_collaboration_quote_files')) {
            return $user->collaboration_id === $quoteFile->collaboration_id;
        }

        if($user->can('handle_own_quote_files')) {
            return $user->id === $quoteFile->user_id;
        }
    }
}
