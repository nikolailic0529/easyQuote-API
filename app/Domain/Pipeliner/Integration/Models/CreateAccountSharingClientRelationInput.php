<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\SharingRoleEnum;

class CreateAccountSharingClientRelationInput extends BaseInput
{
    public function __construct(
        public readonly string $clientId,
        public readonly SharingRoleEnum $role,
    ) {
    }
}
