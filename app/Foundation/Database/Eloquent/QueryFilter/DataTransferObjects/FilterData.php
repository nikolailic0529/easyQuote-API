<?php

namespace App\Foundation\Database\Eloquent\QueryFilter\DataTransferObjects;

use App\Foundation\Database\Eloquent\QueryFilter\DataTransferObjects\Enum\FilterTypeEnum;
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
