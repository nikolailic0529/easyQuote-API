<?php

namespace App\Events\SalesOrder;

use App\Models\SalesOrder;

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