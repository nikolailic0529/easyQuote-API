<?php

namespace App\Domain\Country\DataTransferObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class UpdateCountryData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $iso_3166_2,
        public readonly string|Optional $default_currency_id,
        public readonly string|Optional $currency_code,
        public readonly string|Optional $currency_name,
        public readonly string|Optional $currency_symbol,
    ) {
    }
}
