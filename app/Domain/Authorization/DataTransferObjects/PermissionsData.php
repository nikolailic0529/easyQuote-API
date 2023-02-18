<?php

namespace App\Domain\Authorization\DataTransferObjects;

use Spatie\LaravelData\Data;

final class PermissionsData extends Data
{
    public function __construct(
        public readonly bool $update,
        public readonly bool $delete,
    ) {
    }
}
