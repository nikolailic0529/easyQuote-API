<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\OrgRelationshipTypeEnum;

final class CreateContactRelationInput extends BaseInput
{
    public function __construct(
        public readonly string $contactId,
        public readonly bool $isPrimary,
        public readonly \DateTimeImmutable|InputValueEnum $modified = InputValueEnum::Miss,
        public readonly \DateTimeImmutable|InputValueEnum $created = InputValueEnum::Miss,
        public readonly string|InputValueEnum $comment = InputValueEnum::Miss,
        public readonly string|InputValueEnum $leadOpptyContactGroupRelationId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $positionInGroup = InputValueEnum::Miss,
        public readonly OrgRelationshipTypeEnum|InputValueEnum $relationship = InputValueEnum::Miss,
        public readonly int|InputValueEnum $revision = InputValueEnum::Miss
    ) {
    }
}
