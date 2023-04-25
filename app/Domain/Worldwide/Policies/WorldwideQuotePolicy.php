<?php

namespace App\Domain\Worldwide\Policies;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WorldwideQuotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_own_ww_quotes', 'view_ww_quotes_where_editor'])) {
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

        if ($user->can('view_own_ww_quotes') && $user->role->access->accessWorldwideQuoteDirection === AccessEntityDirection::All) {
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

        if ($user->role->access->accessWorldwideQuoteDirection !== AccessEntityDirection::Owned) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models where editor are granted.
     */
    public function viewEntitiesWhereEditor(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_ww_quotes_where_editor')) {
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

        if ($user->can('view_any_owner_ww_quotes')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorldwideQuote $worldwideQuote): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('view_own_ww_quotes') && $user->cant('view_ww_quotes_where_editor')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('worldwide quote')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $worldwideQuote->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('worldwide quote')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($worldwideQuote->user()->is($user) || $this->userInSharingUsers($worldwideQuote, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('worldwide quote')
            ->reason('You must be either an owner or editor')
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

        if ($user->can('create_ww_quotes')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = $this->getBaseUpdateResponse($user, $worldwideQuote);

        if ($response->allowed()) {
            if (null === $worldwideQuote->submitted_at) {
                return $response;
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->reason('Quote is submitted')
                ->toResponse();
        }

        return $response;
    }

    /**
     * Determine whether the user can change ownership of the worldwide quote.
     */
    public function changeOwnership(User $user, WorldwideQuote $worldwideQuote): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if (!$user->hasPermissionTo('change_ww_quotes_ownership')) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('worldwide quote')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $worldwideQuote->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('worldwide quote')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($worldwideQuote->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('change ownership')
            ->item('worldwide quote')
            ->reason('You must be an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the specified version of the model.
     */
    public function deleteVersion(User $user, WorldwideQuote $worldwideQuote, WorldwideQuoteVersion $version): Response
    {
        $response = $this->getBaseUpdateResponse($user, $worldwideQuote);

        if ($response->allowed()) {
            if (null !== $worldwideQuote->submitted_at) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('worldwide quote version')
                    ->reason('Quote is submitted')
                    ->toResponse();
            }

            if ($worldwideQuote->activeVersion()->is($version)) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('worldwide quote version')
                    ->reason('The version is active')
                    ->toResponse();
            }
        }

        return $response;
    }

    /**
     * Determine whether the user can change status of the model.
     */
    public function changeStatus(User $user, WorldwideQuote $worldwideQuote): Response
    {
        return $this->getBaseUpdateResponse($user, $worldwideQuote);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = (function () use ($user, $worldwideQuote): Response {
            if ($user->hasRole(R_SUPER)) {
                return $this->allow();
            }

            if ($user->cant('delete_own_ww_quotes')) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('worldwide quote')
                    ->toResponse();
            }

            if (!$this->userHasAccessToUnit($user, $worldwideQuote->salesUnit)) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('worldwide quote')
                    ->reason('You don\'t have an access to the unit.')
                    ->toResponse();
            }

            if ($this->userHasAccessToCurrentOrAllUnits($user)) {
                return $this->allow();
            }

            if ($worldwideQuote->user()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->reason('You must be an owner')
                ->toResponse();
        })();

        if ($response->allowed()) {
            if ($this->salesOrderExists($worldwideQuote)) {
                return ResponseBuilder::deny()
                    ->action('delete')
                    ->item('worldwide quote')
                    ->reason('Sales order exists')
                    ->toResponse();
            }

            return $response;
        }

        return $response;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function export(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = $this->view($user, $worldwideQuote);

        if ($response->allowed()) {
            if (null === $worldwideQuote->submitted_at) {
                return ResponseBuilder::deny()
                    ->action('export')
                    ->item('worldwide quote')
                    ->reason('Quote must be submitted')
                    ->toResponse();
            }
        }

        return $response;
    }

    /**
     * Determine whether the user can unravel the model.
     */
    public function unravel(User $user, WorldwideQuote $worldwideQuote): Response
    {
        $response = $this->getBaseUpdateResponse($user, $worldwideQuote);

        if ($response->allowed()) {
            if ($this->salesOrderExists($worldwideQuote)) {
                return ResponseBuilder::deny()
                    ->action('unravel')
                    ->item('worldwide quote')
                    ->reason('Sales order exists')
                    ->toResponse();
            }
        }

        return $response;
    }

    /**
     * Determine whether the user can replicate the model.
     *
     * @return mixed
     */
    public function replicate(User $user, WorldwideQuote $worldwideQuote): Response
    {
        return $this->create($user);
    }

    protected function salesOrderExists(WorldwideQuote $worldwideQuote): bool
    {
        // When explicitly defined sales_order_exists field is present on the model entity,
        // We will use it to check an existence of sales order.
        // This is done for optimization of listing queries.
        if (isset($worldwideQuote->sales_order_exists)) {
            return (bool) $worldwideQuote->sales_order_exists;
        } elseif ($worldwideQuote->relationLoaded('salesOrder')) {
            return $worldwideQuote->salesOrder !== null;
        } else {
            return $worldwideQuote->salesOrder()->exists();
        }
    }

    protected function getBaseUpdateResponse(User $user, WorldwideQuote $worldwideQuote): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_own_ww_quotes') && $user->cant('update_ww_quotes_where_editor')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $worldwideQuote->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('worldwide quote')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($worldwideQuote->user()->is($user) || $this->userInSharingUsers($worldwideQuote, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('worldwide quote')
            ->reason('You must be either an owner or editor')
            ->toResponse();
    }

    protected function userInSharingUsers(WorldwideQuote $quote, User $user): bool
    {
        if ($quote->relationLoaded('sharingUsers')) {
            return $quote->sharingUsers->contains($user);
        }

        $userForeignKey = \once(static function (): string {
            return (new ModelHasSharingUsers())->user()->getForeignKeyName();
        });

        return $quote->sharingUserRelations
            ->lazy()
            ->pluck($userForeignKey)
            ->containsStrict($user->getKey());
    }

    private function userHasAccessToCurrentOrAllUnits(User $user): bool
    {
        return in_array($user->role->access->accessWorldwideQuoteDirection, [AccessEntityDirection::CurrentUnits, AccessEntityDirection::All], true);
    }

    protected function userHasAccessToUnit(User $user, ?SalesUnit $unit): bool
    {
        if ($user->role->access->accessWorldwideQuoteDirection === AccessEntityDirection::All) {
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
}
