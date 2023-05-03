<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\OrgRelationshipTypeEnum;

class CreateOrUpdateContactAccountRelationInput extends BaseInput
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $contactId,
        public readonly bool $isPrimary,
        public readonly bool|InputValueEnum $isAssistant = InputValueEnum::Miss,
        public readonly bool|InputValueEnum $isSibling = InputValueEnum::Miss,
        public readonly string|InputValueEnum $position = InputValueEnum::Miss,
        public readonly OrgRelationshipTypeEnum|InputValueEnum $relationship = InputValueEnum::Miss
    ) {
    }
}
