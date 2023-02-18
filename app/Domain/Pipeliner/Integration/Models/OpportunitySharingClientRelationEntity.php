<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\SharingRoleEnum;
use App\Foundation\Support\Enum\EnumResolver;

class OpportunitySharingClientRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly SharingRoleEnum $role,
        public readonly ClientEntity $client,
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            role: EnumResolver::fromKey(SharingRoleEnum::class, $array['role']),
            client: ClientEntity::fromArray($array['client']),
        );
    }
}
