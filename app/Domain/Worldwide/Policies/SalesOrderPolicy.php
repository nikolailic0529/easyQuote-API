<?php

namespace App\Domain\Worldwide\Policies;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Enum\SalesOrderStatus;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SalesOrderPolicy
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

        if ($user->can('view_own_sales_orders')) {
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

        if ($user->can('view_own_sales_orders') && $user->role->access->accessSalesOrderDirection === AccessEntityDirection::All) {
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

        if ($user->can('view_own_sales_orders') && $user->role->access->accessSalesOrderDirection !== AccessEntityDirection::Owned) {
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

        if ($user->can('view_any_owner_sales_orders')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SalesOrder $salesOrder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('view_own_sales_orders')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('sales order')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $salesOrder->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('sales order')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($salesOrder->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('worldwide quote')
            ->reason('You must be an owner')
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

        if ($user->can('create_sales_orders')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SalesOrder $salesOrder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_own_sales_orders')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('sales order')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $salesOrder->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('sales order')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($salesOrder->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('sales order')
            ->reason('You must be an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SalesOrder $salesOrder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('delete_own_sales_orders')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('sales order')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $salesOrder->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('sales order')
                ->reason('You don\'t have an access to the unit.')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($salesOrder->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('sales order')
            ->reason('You must be an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can cancel the sales order.
     */
    public function cancel(User $user, SalesOrder $salesOrder): Response
    {
        return $this->update($user, $salesOrder);
    }

    /**
     * Determine whether the user can re-submit the sales order.
     */
    public function resubmit(User $user, SalesOrder $salesOrder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('resubmit_sales_orders')) {
            return ResponseBuilder::deny()
                ->action('re-submit')
                ->item('sales order')
                ->toResponse();
        }

        $response = (function () use ($user, $salesOrder): Response {
            if (!$this->userHasAccessToUnit($user, $salesOrder->salesUnit)) {
                return ResponseBuilder::deny()
                    ->action('re-submit')
                    ->item('sales order')
                    ->reason('You don\'t have an access to the unit.')
                    ->toResponse();
            }

            if ($this->userHasAccessToCurrentOrAllUnits($user)) {
                return $this->allow();
            }

            if ($salesOrder->user()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('re-submit')
                ->item('sales order')
                ->reason('You must be an owner')
                ->toResponse();
        })();

        if ($response->allowed()) {
            if (SalesOrderStatus::SENT === $salesOrder->status) {
                return ResponseBuilder::deny()
                    ->action('re-submit')
                    ->item('sales order')
                    ->reason('The sales order is already sent')
                    ->toResponse();
            }
        }

        return $response;
    }

    /**
     * Determine whether the user can refresh status of the sales order.
     */
    public function refreshStatus(User $user, SalesOrder $salesOrder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('refresh_status_of_sales_orders')) {
            return ResponseBuilder::deny()
                ->action('refresh status')
                ->item('sales order')
                ->toResponse();
        }

        if (is_null($salesOrder->submitted_at)) {
            return ResponseBuilder::deny()
                ->action('refresh status')
                ->item('sales order')
                ->reason('The sales order must be submitted')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can export the sales order.
     */
    public function export(User $user, SalesOrder $salesOrder): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('download_sales_order_pdf')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('export')
            ->item('sales order')
            ->toResponse();
    }

    private function userHasAccessToCurrentOrAllUnits(User $user): bool
    {
        return in_array($user->role->access->accessSalesOrderDirection, [AccessEntityDirection::CurrentUnits, AccessEntityDirection::All], true);
    }

    private function userHasAccessToUnit(User $user, ?SalesUnit $unit): bool
    {
        if ($user->role->access->accessSalesOrderDirection === AccessEntityDirection::All) {
            return true;
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
