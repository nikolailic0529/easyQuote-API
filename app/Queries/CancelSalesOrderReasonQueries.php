<?php

namespace App\Queries;

use App\Models\CancelSalesOrderReason;
use Illuminate\Database\Eloquent\Builder;

class CancelSalesOrderReasonQueries
{
    public function listingQuery(): Builder
    {
        return CancelSalesOrderReason::query()
            ->select(['id', 'description'])
            ->orderBy('description');
    }
}
