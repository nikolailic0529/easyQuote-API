<?php

namespace App\Domain\Authorization\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapName(SnakeCaseMapper::class)]
final class CreateRoleData extends Data
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
