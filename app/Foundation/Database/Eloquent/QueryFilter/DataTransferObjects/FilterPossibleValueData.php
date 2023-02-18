<?php

namespace App\Foundation\Database\Eloquent\QueryFilter\DataTransferObjects;

use Spatie\LaravelData\Data;

final class FilterPossibleValueData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly string $value,
    ) {
    }
}
