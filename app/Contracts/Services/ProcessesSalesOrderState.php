<?php

namespace App\Contracts\Services;

use App\DTO\SalesOrder\Cancel\CancelSalesOrderData;
use App\DTO\SalesOrder\Cancel\CancelSalesOrderResult;
use App\DTO\SalesOrder\DraftSalesOrderData;
use App\DTO\SalesOrder\Submit\SubmitSalesOrderResult;
use App\DTO\SalesOrder\UpdateSalesOrderData;
use App\Models\SalesOrder;

interface ProcessesSalesOrderState
{
    /**
     * Draft a newly created Sales Order.
     *
     * @param DraftSalesOrderData $data
     * @return SalesOrder
     */
    public function draftSalesOrder(DraftSalesOrderData $data): SalesOrder;

    /**
     * Update the existing Sales Order.
     *
     * @param UpdateSalesOrderData $data
     * @param SalesOrder $salesOrder
     * @return SalesOrder
     */
    public function updateSalesOrder(UpdateSalesOrderData $data, SalesOrder $salesOrder): SalesOrder;

    /**
     * Submit the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     * @return SubmitSalesOrderResult
     */
    public function submitSalesOrder(SalesOrder $salesOrder): SubmitSalesOrderResult;

    /**
     * Cancel the existing Sales Order.
     *
     * @param CancelSalesOrderData $data
     * @param SalesOrder $salesOrder
     * @return CancelSalesOrderResult
     */
    public function cancelSalesOrder(CancelSalesOrderData $data, SalesOrder $salesOrder): CancelSalesOrderResult;

    /**
     * Unravel the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     */
    public function unravelSalesOrder(SalesOrder $salesOrder): void;

    /**
     * Mark as active the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     */
    public function activateSalesOrder(SalesOrder $salesOrder): void;

    /**
     * Mark as active the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     */
    public function deactivateSalesOrder(SalesOrder $salesOrder): void;

    /**
     * Delete the existing Sales Order.
     *
     * @param SalesOrder $salesOrder
     */
    public function deleteSalesOrder(SalesOrder $salesOrder): void;
}
