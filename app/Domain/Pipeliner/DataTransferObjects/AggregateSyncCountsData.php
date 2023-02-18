<?php

namespace App\Domain\Pipeliner\DataTransferObjects;

use Spatie\LaravelData\Data;

final class AggregateSyncCountsData extends Data
{
    public function __construct(
        public readonly int $opportunities = 0,
        public readonly int $companies = 0,
    ) {
    }
}
