<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\OrgRelationshipTypeEnum;
use DateTimeImmutable;

final class CreateContactRelationInput extends BaseInput
{
    public function __construct(
        public readonly string $contactId,
        public readonly bool $isPrimary,
        public readonly DateTimeImmutable|InputValueEnum $modified = InputValueEnum::Miss,
        public readonly DateTimeImmutable|InputValueEnum $created = InputValueEnum::Miss,
        public readonly string|InputValueEnum $comment = InputValueEnum::Miss,
        public readonly string|InputValueEnum $leadOpptyContactGroupRelationId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $positionInGroup = InputValueEnum::Miss,
        public readonly OrgRelationshipTypeEnum|InputValueEnum $relationship = InputValueEnum::Miss,
        public readonly int|InputValueEnum $revision = InputValueEnum::Miss
    ) {
    }
}