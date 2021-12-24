<?php

namespace App\Events\SalesOrder;

use App\Models\SalesOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SalesOrderDrafted
{
    use Dispatchable, SerializesModels;

    private SalesOrder $salesOrder;

    /**
     * Create a new event instance.
     *
     * @param SalesOrder $salesOrder
     */
    public function __construct(SalesOrder $salesOrder)
    {
        $this->salesOrder = $salesOrder;
    }

    /**
     * @return SalesOrder
     */
    public function getSalesOrder(): SalesOrder
    {
        return $this->salesOrder;
    }
}
