<?php

namespace App\Policies;

use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use App\Services\Auth\UserTeamGate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WorldwideQuotePolicy
{
    use HandlesAuthorization;

    protected UserTeamGate $userTeamGate;

    public function __construct(UserTeamGate $userTeamGate)
    {
        $this->userTeamGate = $userTeamGate;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_ww_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view models of any owner.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAnyOwnerEntities(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function view(User $user, WorldwideQuote $worldwideQuote)
    {
        $hasPermissionTo = value(function () use ($user, $worldwideQuote): bool {

            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if (false === $user->can('view_own_ww_quotes')) {
                return false;
            }

            $ownerKeyOfEntity = $worldwideQuote->{$worldwideQuote->user()->getForeignKeyName()};

            if ($user->getKey() === $ownerKeyOfEntity) {
                return true;
            }

            if ($this->userTeamGate->isUserLedByUser($ownerKeyOfEntity, $user)) {
                return true;
            }

            return false;

        });

        if ($hasPermissionTo) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_ww_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function update(User $user, WorldwideQuote $worldwideQuote)
    {
        $hasPermissionTo = value(function () use ($user, $worldwideQuote): bool {

            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if (false === $user->can('update_own_ww_quotes')) {
                return false;
            }

            $ownerKeyOfEntity = $worldwideQuote->{$worldwideQuote->user()->getForeignKeyName()};

            if ($user->getKey() === $ownerKeyOfEntity) {
                return true;
            }

            if ($this->userTeamGate->isUserLedByUser($ownerKeyOfEntity, $user)) {
                return true;
            }

            return false;

        });

        if (false === $hasPermissionTo) {
            return false;
        }

        if ($worldwideQuote->submitted_at === null) {
            return true;
        }

        if ($worldwideQuote->submitted_at !== null) {
            return $this->deny('You can\'t to update the submitted Worldwide Quote', 422);
        }
    }

    /**
     * Determine whether the user can delete the specified version of the model.
     *
     * @param User $user
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteVersion $version
     * @return bool|Response
     */
    public function deleteVersion(User $user, WorldwideQuote $worldwideQuote, WorldwideQuoteVersion $version)
    {
        $hasPermissionTo = value(function () use ($user, $worldwideQuote): bool {

            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if (false === $user->can('update_own_ww_quotes')) {
                return false;
            }

            $ownerKeyOfEntity = $worldwideQuote->{$worldwideQuote->user()->getForeignKeyName()};

            if ($user->getKey() === $ownerKeyOfEntity) {
                return true;
            }

            if ($this->userTeamGate->isUserLedByUser($ownerKeyOfEntity, $user)) {
                return true;
            }

            return false;

        });

        if (false === $hasPermissionTo) {
            return false;
        }

        if ($worldwideQuote->submitted_at !== null) {
            return $this->deny('You can\'t to delete version of the submitted Worldwide Quote', HttpResponse::HTTP_FORBIDDEN);
        }

        if ($worldwideQuote->{$worldwideQuote->activeVersion()->getForeignKeyName()} === $version->getKey()) {
            return $this->deny('You can\'t to delete the active version of Worldwide Quote.', HttpResponse::HTTP_FORBIDDEN);
        }

        return true;
    }

    /**
     * Determine whether the user can change status of the model.
     *
     * @param User $user
     * @param WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function changeStatus(User $user, WorldwideQuote $worldwideQuote)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (false === $user->can('update_own_ww_quotes')) {
            return false;
        }

        $ownerOrLedBy = value(function () use ($user, $worldwideQuote): bool {
            $ownerKeyOfEntity = $worldwideQuote->{$worldwideQuote->user()->getForeignKeyName()};

            if ($user->getKey() === $ownerKeyOfEntity) {
                return true;
            }

            if ($this->userTeamGate->isUserLedByUser($ownerKeyOfEntity, $user)) {
                return true;
            }

            return false;
        });

        if ($ownerOrLedBy) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function delete(User $user, WorldwideQuote $worldwideQuote)
    {
        $salesOrderExistsMessage = 'You have to delete the Sales Order in order to delete the Quote.';

        if ($user->hasRole(R_SUPER)) {
            return $this->ensureSalesOrderDoesNotExist($worldwideQuote, $salesOrderExistsMessage);
        }

        if (false === $user->can('delete_own_ww_quotes')) {
            return false;
        }

        $ownerOrLedBy = value(function () use ($user, $worldwideQuote): bool {
            $ownerKeyOfEntity = $worldwideQuote->{$worldwideQuote->user()->getForeignKeyName()};

            if ($user->getKey() === $ownerKeyOfEntity) {
                return true;
            }

            if ($this->userTeamGate->isUserLedByUser($ownerKeyOfEntity, $user)) {
                return true;
            }

            return false;
        });

        if ($ownerOrLedBy) {
            return $this->ensureSalesOrderDoesNotExist($worldwideQuote, $salesOrderExistsMessage);
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function export(User $user, WorldwideQuote $worldwideQuote)
    {
        $canView = value(function () use ($worldwideQuote, $user) {
            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if ($user->can('view_own_ww_quotes')) {
                return true;
            }

            return false;
        });

        if (!$canView) {
            return false;
        }

        if (is_null($worldwideQuote->submitted_at)) {
            return $this->deny("You have submit the Quote first in order to perform export.");
        }

        return true;
    }

    /**
     * Determine whether the user can unravel the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function unravel(User $user, WorldwideQuote $worldwideQuote)
    {
        $salesOrderExistsMessage = 'You have to delete the Sales Order in order to unravel the Quote.';

        $hasPermissionTo = value(function () use ($user, $worldwideQuote): bool {

            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if (false === $user->can('update_own_ww_quotes')) {
                return false;
            }

            $ownerKeyOfEntity = $worldwideQuote->{$worldwideQuote->user()->getForeignKeyName()};

            if ($user->getKey() === $ownerKeyOfEntity) {
                return true;
            }

            if ($this->userTeamGate->isUserLedByUser($ownerKeyOfEntity, $user)) {
                return true;
            }

            return false;

        });

        if (false === $hasPermissionTo) {
            return false;
        }

        return $this->ensureSalesOrderDoesNotExist($worldwideQuote, $salesOrderExistsMessage);
    }

    /**
     * Determine whether the user can replicate the model.
     *
     * @param User $user
     * @param WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function replicate(User $user, WorldwideQuote $worldwideQuote)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_ww_quotes')) {
            return true;
        }
    }

    protected function ensureSalesOrderDoesNotExist(WorldwideQuote $worldwideQuote, string $denyMessage): Response
    {
        // When explicitly defined sales_order_exists field is present on the model entity,
        // We will use it to check an existence of sales order.
        // This is done for optimization of listing queries.
        if (!is_null($worldwideQuote->sales_order_exists)) {
            if ($worldwideQuote->sales_order_exists) {
                return $this->deny($denyMessage);
            }

            return $this->allow();
        }

        if ($worldwideQuote->salesOrder()->exists()) {
            return $this->deny($denyMessage);
        }

        return $this->allow();
    }
}
