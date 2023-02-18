<?php

namespace App\Domain\Worldwide\Events\SalesOrder;

use App\Domain\Worldwide\Models\SalesOrder;

final class SalesOrderExported
{
    public function __construct(protected SalesOrder $salesOrder)
    {
    }

    public function getSalesOrder(): SalesOrder
    {
        return $this->salesOrder;
    }
}
