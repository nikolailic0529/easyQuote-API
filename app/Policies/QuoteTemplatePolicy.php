<?php namespace App\Policies;

use App\Models \ {
    User,
    QuoteTemplate\QuoteTemplate
};
use Illuminate\Auth\Access \ {
    HandlesAuthorization,
    Response
};

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
        if($user->can('view_templates')) {
            return true;
        }
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
        if($user->can('view_templates')) {
            return true;
        }
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
        if($quoteTemplate->isSystem()) {
            return Response::deny(__('template.system_updating_exception'));
        }

        if($user->can('update_templates')) {
            return true;
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
        if($quoteTemplate->isSystem()) {
            return Response::deny(__('template.system_deleting_exception'));
        }

        if($user->can('delete_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can make copy of the quote.
     *
     * @param User $user
     * @param QuoteTemplate $quoteTemplate
     * @return mixed
     */
    public function copy(User $user, QuoteTemplate $quoteTemplate)
    {
        return $this->create($user) && $this->view($user, $quoteTemplate);
    }
}
