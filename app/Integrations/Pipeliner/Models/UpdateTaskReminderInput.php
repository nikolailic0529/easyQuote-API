<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\ReminderStatusEnum;
use App\Integrations\Pipeliner\Serializers\DateTimeSerializer;

class UpdateTaskReminderInput extends BaseInput
{
    public function __construct(public readonly string $id,
                                public readonly string|InputValueEnum $ownerId = InputValueEnum::Miss,
                                       #[SerializeWith(DateTimeSerializer::class, 'Y-m-d H:i:s')] public readonly \DateTimeImmutable|InputValueEnum $setDate = InputValueEnum::Miss,
                                public readonly ReminderStatusEnum|InputValueEnum $status = InputValueEnum::Miss)
    {
    }
}