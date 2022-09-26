<?php

namespace App\Policies;

use App\Enum\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SalesOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return Response
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
     * Determine whether the user can view models of any owner.
     *
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
     * @param  \App\Models\SalesOrder  $salesOrder
     * @return Response
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

        if ($user->salesUnitsFromLedTeams->contains($salesOrder->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($salesOrder->salesUnit)) {
            if ($salesOrder->user()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('view')
                ->item('sales order')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->deny();
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

        if ($user->can('create_sales_orders')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SalesOrder  $salesOrder
     * @return Response
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

        if ($user->salesUnitsFromLedTeams->contains($salesOrder->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($salesOrder->salesUnit)) {
            if ($salesOrder->user()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('sales order')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SalesOrder  $salesOrder
     * @return Response
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

        if ($user->salesUnitsFromLedTeams->contains($salesOrder->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($salesOrder->salesUnit)) {
            if ($salesOrder->user()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('delete')
                ->item('sales order')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can cancel the sales order.
     *
     * @param  User  $user
     * @param  SalesOrder  $salesOrder
     * @return Response
     */
    public function cancel(User $user, SalesOrder $salesOrder): Response
    {
        return $this->update($user, $salesOrder);
    }

    /**
     * Determine whether the user can re-submit the sales order.
     *
     * @param  User  $user
     * @param  SalesOrder  $salesOrder
     * @return Response
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
            if ($user->salesUnitsFromLedTeams->contains($salesOrder->salesUnit)) {
                return $this->allow();
            }

            if ($user->salesUnits->contains($salesOrder->salesUnit)) {
                if ($salesOrder->user()->is($user)) {
                    return $this->allow();
                }

                return ResponseBuilder::deny()
                    ->action('re-submit')
                    ->item('sales order')
                    ->reason('You must be an owner')
                    ->toResponse();
            }

            return $this->deny();
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
     *
     * @param  User  $user
     * @param  SalesOrder  $salesOrder
     * @return Response
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
     *
     * @param  User  $user
     * @param  SalesOrder  $salesOrder
     * @return Response
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
}
