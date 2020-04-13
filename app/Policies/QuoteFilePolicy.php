<?php

namespace App\Policies;

use App\Models\{
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
        if ($user->can('view_quote_files')) {
            return true;
        }
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
        if ($user->can('view_quote_files')) {
            return true;
        }

        return $this->deny('You have not any permission to this module.');
    }

    /**
     * Determine whether the user can create quote files.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->can('create_quote_files')) {
            return true;
        }

        $readonly = $user->can('view_quote_files')
            ? 'You have readonly access.'
            : 'You have not any permission to this module.';

        return $this->deny('You can not upload quote files. ' . $readonly);
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
        if ($user->can('update_quote_files')) {
            return true;
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
        if ($user->can('delete_quote_files')) {
            return true;
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
        if ($user->can('handle_quote_files')) {
            return true;
        }

        $readonly = $user->can('view_quote_files')
            ? 'You have readonly access.'
            : 'You have not any permission to this module.';

        return $this->deny('You can not import quote files. ' . $readonly);
    }
}
