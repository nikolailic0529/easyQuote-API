<?php

namespace App\DTO\QueryFilter;

use Spatie\LaravelData\Data;

final class FilterPossibleValueData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly string $value,
    )
    {
    }
}