<?php

namespace App\Policies;

use App\Enum\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesOrderPolicy
{
    use HandlesAuthorization;

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

        if ($user->can('view_own_sales_orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view models of any owner.
     *
     * @param User $user
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
     * @param \App\Models\SalesOrder $salesOrder
     * @return mixed
     */
    public function view(User $user, SalesOrder $salesOrder)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_sales_orders') && $salesOrder->{$salesOrder->user()->getForeignKeyName()} === $user->getKey()) {
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

        if ($user->can('create_sales_orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\SalesOrder $salesOrder
     * @return mixed
     */
    public function update(User $user, SalesOrder $salesOrder)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('update_own_sales_orders') && $salesOrder->{$salesOrder->user()->getForeignKeyName()} === $user->getKey()) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\SalesOrder $salesOrder
     * @return mixed
     */
    public function delete(User $user, SalesOrder $salesOrder)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('delete_own_sales_orders') && $salesOrder->{$salesOrder->user()->getForeignKeyName()} === $user->getKey()) {
            return true;
        }
    }

    /**
     * Determine whether the user can cancel the sales order.
     *
     * @param User $user
     * @param SalesOrder $salesOrder
     * @return mixed
     */
    public function cancel(User $user, SalesOrder $salesOrder)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('cancel_sales_orders')) {
            return true;
        }
    }

    /**
     * Determine whether the user can re-submit the sales order.
     *
     * @param User $user
     * @param SalesOrder $salesOrder
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resubmit(User $user, SalesOrder $salesOrder)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->cant('resubmit_sales_orders')) {
            return false;
        }

        if (SalesOrderStatus::SENT === $salesOrder->status) {
            return $this->deny("You can not re-submit the already sent sales order.");
        }

        return true;
    }

    /**
     * Determine whether the user can refresh status of the sales order.
     *
     * @param User $user
     * @param SalesOrder $salesOrder
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function refreshStatus(User $user, SalesOrder $salesOrder)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->cant('refresh_status_of_sales_orders')) {
            return false;
        }

        if (is_null($salesOrder->submitted_at)) {
            return $this->deny("You can not refresh status of the drafted sales order.");
        }

        return true;
    }

    /**
     * Determine whether the user can export the sales order.
     *
     * @param User $user
     * @param SalesOrder $salesOrder
     * @return bool|void
     */
    public function export(User $user, SalesOrder $salesOrder)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('download_sales_order_pdf')) {
            return true;
        }
    }
}
