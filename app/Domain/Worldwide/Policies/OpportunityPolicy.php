<?php

namespace App\Domain\Worldwide\Policies;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\Authorization\Enum\AccessEntityPipelineDirection;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
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
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_opportunities', 'view_opportunities_where_editor'])) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view all models.
     */
    public function viewAll(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_opportunities')
            && $user->role->access->accessOpportunityDirection === AccessEntityDirection::All
            && $user->role->access->accessOpportunityPipelineDirection === AccessEntityPipelineDirection::All) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models related to the assigned units.
     */
    public function viewCurrentUnitsEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->role->access->accessOpportunityDirection !== AccessEntityDirection::Owned) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models where editor rights are granted.
     */
    public function viewEntitiesWhereEditor(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_opportunities_where_editor')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models related to the all pipelines.
     */
    public function viewEntitiesOfAllPipelines(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->role->access->accessOpportunityPipelineDirection === AccessEntityPipelineDirection::All) {
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

        if ($user->cant('view_opportunities') && $user->cant('view_opportunities_where_editor')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('opportunity')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $opportunity->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('opportunity')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if (!$this->userHasAccessToPipeline($opportunity, $user)) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('opportunity')
                ->reason('You don\'t have an access to the pipeline.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($opportunity->user()->is($user)
            || $opportunity->accountManager()->is($user)
            || $this->userInSharingUsers($opportunity, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('opportunity')
            ->reason('You must be either an owner, account manager or editor')
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

        if ($user->cant('update_own_opportunities') && $user->cant('update_opportunities_where_editor')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('opportunity')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $opportunity->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('opportunity')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if (!$this->userHasAccessToPipeline($opportunity, $user)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('opportunity')
                ->reason('You don\'t have an access to the pipeline.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($opportunity->user()->is($user)
            || $opportunity->accountManager()->is($user)
            || $this->userInSharingUsers($opportunity, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('opportunity')
            ->reason('You must be either an owner, account manager or editor')
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

        if ($user->cant('change_opportunities_ownership')) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('opportunity')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $opportunity->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('opportunity')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($opportunity->user()->is($user) || $opportunity->accountManager()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('change ownership')
            ->item('opportunity')
            ->reason('You must be an owner or account manager')
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

            if (!$this->userHasAccessToUnit($user, $opportunity->salesUnit)) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('opportunity')
                    ->reason('You don\'t have an access to the unit.')
                    ->toResponse();
            }

            if (!$this->userHasAccessToPipeline($opportunity, $user)) {
                return ResponseBuilder::deny()
                    ->action('update')
                    ->item('opportunity')
                    ->reason('You don\'t have an access to the pipeline.')
                    ->toResponse();
            }

            if ($this->userHasAccessToCurrentOrAllUnits($user)) {
                return $this->allow();
            }

            if ($opportunity->owner()->is($user) || $opportunity->accountManager()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('delete')
                ->item('opportunity')
                ->reason('You must be an owner or account manager')
                ->toResponse();
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

    private function userHasAccessToCurrentOrAllUnits(User $user): bool
    {
        return in_array($user->role->access->accessOpportunityDirection, [AccessEntityDirection::CurrentUnits, AccessEntityDirection::All], true);
    }

    private function userHasAccessToUnit(User $user, ?SalesUnit $unit): bool
    {
        if ($user->role->access->accessOpportunityDirection === AccessEntityDirection::All) {
            return true;
        }

        if (!$unit) {
            return false;
        }

        if ($user->salesUnitsFromLedTeams->contains($unit)) {
            return true;
        }

        if ($user->salesUnits->contains($unit)) {
            return true;
        }

        return false;
    }

    protected function userHasAccessToPipeline(Opportunity $opp, User $user): bool
    {
        if ($user->role->access->accessOpportunityPipelineDirection === AccessEntityPipelineDirection::All) {
            return true;
        }

        return $user->role->access->allowedOpportunityPipelines->toCollection()
            ->pluck('pipelineId')
            ->containsStrict($opp->pipeline()->getParentKey());
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
