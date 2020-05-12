<?php

namespace App\DTO;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\DataTransferObjectCollection;

class CustomersSummary extends DataTransferObjectCollection
{
    public static function create($data, float $baseRate): CustomersSummary
    {
        $collection = Collection::wrap($data)->map(fn ($item) => CustomerSummary::create($item, $baseRate));

        return new static($collection->toArray());
    }
}