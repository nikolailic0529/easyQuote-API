<?php

namespace App\Domain\Worldwide\Contracts;

use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Cancel\CancelSalesOrderData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Cancel\CancelSalesOrderResult;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\DraftSalesOrderData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitSalesOrderResult;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\UpdateSalesOrderData;
use App\Domain\Worldwide\Models\SalesOrder;

interface ProcessesSalesOrderState
{
    /**
     * Draft a newly created Sales Order.
     */
    public function draftSalesOrder(DraftSalesOrderData $data): SalesOrder;

    /**
     * Update the existing Sales Order.
     */
    public function updateSalesOrder(UpdateSalesOrderData $data, SalesOrder $salesOrder): SalesOrder;

    /**
     * Submit the existing Sales Order.
     */
    public function submitSalesOrder(SalesOrder $salesOrder): SubmitSalesOrderResult;

    /**
     * Cancel the existing Sales Order.
     */
    public function cancelSalesOrder(CancelSalesOrderData $data, SalesOrder $salesOrder): CancelSalesOrderResult;

    /**
     * Unravel the existing Sales Order.
     */
    public function unravelSalesOrder(SalesOrder $salesOrder): void;

    /**
     * Mark as active the existing Sales Order.
     */
    public function activateSalesOrder(SalesOrder $salesOrder): void;

    /**
     * Mark as active the existing Sales Order.
     */
    public function deactivateSalesOrder(SalesOrder $salesOrder): void;

    /**
     * Delete the existing Sales Order.
     */
    public function deleteSalesOrder(SalesOrder $salesOrder): void;
}
