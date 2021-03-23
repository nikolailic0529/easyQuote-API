<?php

namespace App\Queries;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;

class VendorQueries
{
    public function listingQuery(): Builder
    {
        return Vendor::query()
            ->select(
                'id',
                'name',
                'short_code'
            );
    }
}
