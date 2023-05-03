<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

final class CreateNoteInput extends BaseInput
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $note,
        public readonly string|null|InputValueEnum $accountId = InputValueEnum::Miss,
        public readonly string|null|InputValueEnum $contactId = InputValueEnum::Miss,
        public readonly string|null|InputValueEnum $projectId = InputValueEnum::Miss,
        public readonly string|null|InputValueEnum $leadOpptyId = InputValueEnum::Miss
    ) {
    }
}
