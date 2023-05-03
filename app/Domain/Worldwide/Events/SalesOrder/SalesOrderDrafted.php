<?php

namespace App\Domain\Worldwide\Events\SalesOrder;

use App\Domain\Worldwide\Models\SalesOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SalesOrderDrafted
{
    use Dispatchable;
    use SerializesModels;

    private SalesOrder $salesOrder;

    /**
     * Create a new event instance.
     */
    public function __construct(SalesOrder $salesOrder)
    {
        $this->salesOrder = $salesOrder;
    }

    public function getSalesOrder(): SalesOrder
    {
        return $this->salesOrder;
    }
}
