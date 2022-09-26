<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use App\Services\Auth\UserTeamGate;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OpportunityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
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
     * @param  User  $user
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Opportunity  $opportunity
     * @return Response
     */
    public function view(User $user, Opportunity $opportunity): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('view_opportunities')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('opportunity')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($opportunity->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($opportunity->salesUnit)) {
            if (($opportunity->owner()->is($user) || $opportunity->accountManager()->is($user))) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('view')
                ->item('opportunity')
                ->reason('You must be an owner or account manager')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('opportunity')
            ->toResponse();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Opportunity  $opportunity
     * @return Response
     */
    public function update(User $user, Opportunity $opportunity): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_own_opportunities')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('opportunity')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($opportunity->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($opportunity->salesUnit)) {
            if (($opportunity->owner()->is($user) || $opportunity->accountManager()->is($user))) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('view')
                ->item('opportunity')
                ->reason('You must be an owner or account manager')
                ->toResponse();
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

        return ResponseBuilder::deny()
            ->action('update')
            ->item('opportunity')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Opportunity  $opportunity
     * @return mixed
     */
    public function delete(User $user, Opportunity $opportunity): Response
    {
        $response = (function () use ($opportunity, $user): Response {
            if ($user->hasRole(R_SUPER)) {
                return $this->allow();
            }

            if ($user->cant('delete_own_opportunities')) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('opportunity')
                    ->toResponse();
            }

            if ($user->salesUnitsFromLedTeams->contains($opportunity->salesUnit)) {
                return $this->allow();
            }

            if ($user->salesUnits->contains($opportunity->salesUnit)) {
                if (($opportunity->owner()->is($user) || $opportunity->accountManager()->is($user))) {
                    return $this->allow();
                }

                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('opportunity')
                    ->reason('You must be an owner or account manager')
                    ->toResponse();
            }

            return $this->deny();
        })();

        if ($response->allowed()) {
            if ($opportunity->quotes_exist ?? $opportunity->worldwideQuotes()->exists()) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('opportunity')
                    ->reason('Opportunity is used in quotes')
                    ->toResponse();
            }
        }

        return $response;
    }
}
