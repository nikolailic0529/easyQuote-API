<?php

namespace App\Policies;

use App\Services\Auth\UserTeamGate;
use App\Models\{
    User,
    Template\QuoteTemplate
};
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteTemplatePolicy
{
    use HandlesAuthorization;

    public function __construct(protected UserTeamGate $userTeamGate)
    {
    }

    /**
     * Determine whether the user can view any quote templates.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_quote_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the quote template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\QuoteTemplate  $quoteTemplate
     * @return mixed
     */
    public function view(User $user, QuoteTemplate $quoteTemplate)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_quote_templates')) {
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
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_quote_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the quote template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\QuoteTemplate  $quoteTemplate
     * @return mixed
     */
    public function update(User $user, QuoteTemplate $quoteTemplate)
    {
        if ($quoteTemplate->isSystem()) {
            return $this->deny(QTSU_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->cannot('update_own_quote_templates')) {
            return false;
        }

        if ($quoteTemplate->user()->is($user)) {
            return true;
        }

        if ($this->userTeamGate->isUserLedByUser($quoteTemplate->user()->getParentKey(), $user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the quote template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\QuoteTemplate  $quoteTemplate
     * @return mixed
     */
    public function delete(User $user, QuoteTemplate $quoteTemplate)
    {
        if ($quoteTemplate->isSystem()) {
            return $this->deny(QTSD_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }
        
        if ($user->cannot('delete_own_quote_templates')) {
            return false;
        }

        if ($quoteTemplate->user()->is($user)) {
            return true;
        }

        if ($this->userTeamGate->isUserLedByUser($quoteTemplate->user()->getParentKey(), $user)) {
            return true;
        }
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
