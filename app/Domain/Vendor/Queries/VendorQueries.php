<?php

namespace App\Domain\Vendor\Queries;

use App\Domain\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;

class VendorQueries
{
    public function listOfActiveVendorsQuery(): Builder
    {
        return $this->listingQuery()
            ->whereNotNull('activated_at');
    }

    public function listingQuery(): Builder
    {
        return Vendor::query()
            ->select([
                'id',
                'name',
                'short_code',
            ]);
    }
}
