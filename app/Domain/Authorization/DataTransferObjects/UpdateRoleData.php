<?php

namespace App\Domain\Authorization\DataTransferObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class UpdateRoleData extends Data
{
    /**
     * @param list<string> $permissions
     */
    public function __construct(
        public readonly string $name,
        public readonly array $permissions,
        public readonly SetAccessData|Optional $accessData,
    ) {
    }
}
