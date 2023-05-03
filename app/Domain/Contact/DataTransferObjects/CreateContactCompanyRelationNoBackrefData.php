<?php

namespace App\Domain\Contact\DataTransferObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class CreateContactCompanyRelationNoBackrefData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly bool|Optional $is_default,
    ) {
    }
}
