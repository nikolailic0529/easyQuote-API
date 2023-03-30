<?php

namespace App\Domain\Worldwide\Policies;

use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OpportunityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
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
            if ($opportunity->owner()->is($user) || $opportunity->accountManager()->is($user) || $this->userInSharingUsers($opportunity, $user)) {
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
            if ($opportunity->owner()->is($user) || $opportunity->accountManager()
                    ->is($user) || $this->userInSharingUsers($opportunity, $user)) {
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
     * Determine whether the user can update the opportunity.
     */
    public function changeOwnership(User $user, Opportunity $opportunity): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if (!$user->hasPermissionTo('change_opportunities_ownership')) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('opportunity')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($opportunity->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($opportunity->salesUnit)) {
            if ($opportunity->owner()->is($user) || $opportunity->accountManager()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('opportunity')
                ->reason('You must be an owner, account manager, or editor')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('change ownership')
            ->item('opportunity')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the model.
     *
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
                if ($opportunity->owner()->is($user) || $opportunity->accountManager()->is($user) || $this->userInSharingUsers($opportunity, $user)) {
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
            if ($this->quotesExist($opportunity)) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('opportunity')
                    ->reason('Opportunity is used in quotes')
                    ->toResponse();
            }
        }

        return $response;
    }

    protected function userInSharingUsers(Opportunity $opp, User $user): bool
    {
        $userForeignKey = \once(static function (): string {
            return (new ModelHasSharingUsers())->user()->getForeignKeyName();
        });

        return $opp->sharingUserRelations
            ->lazy()
            ->pluck($userForeignKey)
            ->containsStrict($user->getKey());
    }

    protected function quotesExist(Opportunity $opportunity): bool
    {
        // When explicitly defined quotes_exist field is present on the model entity,
        // We will use it to check an existence of sales order.
        // This is done for optimization of listing queries.
        if (isset($opportunity->quotes_exist)) {
            return (bool) $opportunity->quotes_exist;
        } elseif ($opportunity->relationLoaded('worldwideQuotes')) {
            return $opportunity->worldwideQuotes->isNotEmpty();
        } else {
            return $opportunity->worldwideQuotes()->exists();
        }
    }
}
