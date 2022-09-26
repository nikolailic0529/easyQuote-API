<?php

namespace App\DTO\QueryFilter;

use App\DTO\QueryFilter\Enum\FilterTypeEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class FilterData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly FilterTypeEnum $type,
        public readonly string $parameter,
        #[DataCollectionOf(FilterPossibleValueData::class)]
        public readonly DataCollection $possible_values,
    ) {
    }
}