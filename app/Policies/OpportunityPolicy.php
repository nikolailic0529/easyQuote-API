<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use App\Services\Auth\UserTeamGate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OpportunityPolicy
{
    use HandlesAuthorization;

    public function __construct(protected UserTeamGate $userTeamGate)
    {
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user): Response
    {
        if ($user->can('view_opportunities')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models of any owner.
     * @param User $user
     * @return Response
     */
    public function viewAnyOwnerEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Opportunity $opportunity
     * @return Response
     */
    public function view(User $user, Opportunity $opportunity): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('view_opportunities')) {
            return $this->deny(__('access.cant_view', ['item' => 'opportunity']));
        }

        if ($user->salesUnits->doesntContain($opportunity->salesUnit)) {
            return $this->deny(__('access.only_assigned_units'));
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\Models\User $user
     * @return Response
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('create_opportunities')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Opportunity $opportunity
     * @return Response
     */
    public function update(User $user, Opportunity $opportunity): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_own_opportunities')) {
            return $this->deny(__('access.cant_update', ['item' => 'opportunity']));
        }

        if ($user->salesUnits->doesntContain($opportunity->salesUnit)) {
            return $this->deny(__('access.only_assigned_units'));
        }

        if ($user->getKey() === $opportunity->accountManager()->getParentKey()) {
            return $this->allow();
        }

        if ($user->getKey() === $opportunity->user()->getParentKey()) {
            return $this->allow();
        }

        if ($this->userTeamGate->isLedByUser($opportunity->user()->getParentKey(), $user)) {
            return $this->allow();
        }

        if ($this->userTeamGate->isLedByUser($opportunity->accountManager()->getParentKey(), $user)) {
            return $this->allow();
        }

        // Allow the user to update an opportunity when the user can update any quote associated with it.
        if (!isset($opportunity->quotes_exist) || $opportunity->quotes_exist) {
            $userCanUpdateAnyQuoteOfOpportunity = $opportunity->worldwideQuotes
                ->contains(static function (WorldwideQuote $quote) use ($user): bool {
                    return $user->can('update', $quote);
                });

            if ($userCanUpdateAnyQuoteOfOpportunity) {
                return $this->allow();
            }
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Opportunity $opportunity
     * @return mixed
     */
    public function delete(User $user, Opportunity $opportunity): Response
    {
        $opportunityHasQuotes = !is_null($opportunity->quotes_exist)
            ? (bool)$opportunity->quotes_exist
            : $opportunity->worldwideQuotes()->exists();

        $opportunityHasQuotesMessage = __('access.cant_delete_because', ["item" => "the opportunity", "It's attached to one or more quotes."]);

        if ($user->hasRole(R_SUPER)) {
            return $opportunityHasQuotes ? $this->deny($opportunityHasQuotesMessage) : $this->allow();
        }

        if ($user->cant('delete_own_opportunities')) {
            return $this->deny(__('access.cant_delete', ['item' => 'any opportunity']));
        }

        if ($user->salesUnits->doesntContain($opportunity->salesUnit)) {
            return $this->deny(__('access.only_assigned_units'));
        }

        if ($user->getKey() === $opportunity->accountManager()->getParentKey()) {
            return $opportunityHasQuotes ? $this->deny($opportunityHasQuotesMessage) : $this->allow();
        }

        if ($user->getKey() === $opportunity->user()->getParentKey()) {
            return $opportunityHasQuotes ? $this->deny($opportunityHasQuotesMessage) : $this->allow();
        }

        if ($this->userTeamGate->isLedByUser($opportunity->user()->getParentKey(), $user)) {
            return $opportunityHasQuotes ? $this->deny($opportunityHasQuotesMessage) : $this->allow();
        }

        if ($this->userTeamGate->isLedByUser($opportunity->accountManager()->getParentKey(), $user)) {
            return $opportunityHasQuotes ? $this->deny($opportunityHasQuotesMessage) : $this->allow();
        }

        return $this->deny();
    }
}
