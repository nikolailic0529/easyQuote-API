<?php namespace App\Policies;

use App\Models \ {
    User,
    QuoteTemplate\QuoteTemplate
};
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quote templates.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the quote template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\QuoteTemplate  $quoteTemplate
     * @return mixed
     */
    public function view(User $user, QuoteTemplate $quoteTemplate)
    {
        return $user->collaboration_id === $quoteTemplate->collaboration_id || $quoteTemplate->isSystem();
    }

    /**
     * Determine whether the user can create quote templates.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if($user->can('create_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the quote template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\QuoteTemplate  $quoteTemplate
     * @return mixed
     */
    public function update(User $user, QuoteTemplate $quoteTemplate)
    {
        if($user->can('update_collaboration_templates')) {
            return $user->collaboration_id === $quoteTemplate->collaboration_id;
        }

        if($user->can('update_own_templates')) {
            return $user->id === $quoteTemplate->user_id;
        }
    }

    /**
     * Determine whether the user can delete the quote template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\QuoteTemplate  $quoteTemplate
     * @return mixed
     */
    public function delete(User $user, QuoteTemplate $quoteTemplate)
    {
        if($user->can('delete_collaboration_templates')) {
            return $user->collaboration_id === $quoteTemplate->collaboration_id;
        }

        if($user->can('update_own_templates')) {
            return $user->id === $quoteTemplate->user_id;
        }
    }
}
