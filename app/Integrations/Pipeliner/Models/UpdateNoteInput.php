<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

final class UpdateNoteInput extends BaseInput
{
    public function __construct(public readonly string $id,
                                public readonly string|InputValueEnum $ownerId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum $note = InputValueEnum::Miss,
                                public readonly string|null|InputValueEnum $accountId = InputValueEnum::Miss,
                                public readonly string|null|InputValueEnum $contactId = InputValueEnum::Miss,
                                public readonly string|null|InputValueEnum $projectId = InputValueEnum::Miss,
                                public readonly string|null|InputValueEnum $leadOpptyId = InputValueEnum::Miss)
    {
    }

}