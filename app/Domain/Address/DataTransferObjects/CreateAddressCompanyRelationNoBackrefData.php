<?php

namespace App\Domain\Address\DataTransferObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class CreateAddressCompanyRelationNoBackrefData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly bool|Optional $is_default,
    ) {
    }
}
