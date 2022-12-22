<?php

namespace App\DTO\Access;

use Spatie\LaravelData\Data;

final class PermissionsData extends Data
{
    public function __construct(
        public readonly bool $update,
        public readonly bool $delete,
    ) {
    }
}