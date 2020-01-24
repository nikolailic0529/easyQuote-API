<?php

namespace App\Policies;

use App\Models\{
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
        return $user->can('view_templates');
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
        return $user->can('view_templates');
    }

    /**
     * Determine whether the user can create quote templates.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('create_templates');
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
        if ($quoteTemplate->isSystem()) {
            return $this->deny(QTSU_01);
        }

        return $user->can('update_templates');
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
        if ($quoteTemplate->isSystem()) {
            return $this->deny(QTSD_01);
        }

        return $user->can('delete_templates');
    }

    /**
     * Determine whether the user can make copy of the template.
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
