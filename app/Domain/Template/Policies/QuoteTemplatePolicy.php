<?php

namespace App\Domain\Template\Policies;

use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\User\Models\User;
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

        if ($this->userTeamGate->isLedByUser($quoteTemplate->user()->getParentKey(), $user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the quote template.
     *
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

        if ($this->userTeamGate->isLedByUser($quoteTemplate->user()->getParentKey(), $user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can make copy of the template.
     *
     * @return mixed
     */
    public function copy(User $user, QuoteTemplate $quoteTemplate)
    {
        return $this->create($user) && $this->view($user, $quoteTemplate);
    }
}
