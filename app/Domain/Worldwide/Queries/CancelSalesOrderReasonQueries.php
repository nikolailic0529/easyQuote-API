<?php

namespace App\Domain\Worldwide\Queries;

use App\Domain\Worldwide\Models\CancelSalesOrderReason;
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
