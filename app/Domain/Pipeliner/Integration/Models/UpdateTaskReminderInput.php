<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Attributes\SerializeWith;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\ReminderStatusEnum;
use App\Domain\Pipeliner\Integration\Serializers\DateTimeSerializer;

class UpdateTaskReminderInput extends BaseInput
{
    public function __construct(
        public readonly string $id,
        public readonly string|InputValueEnum $ownerId = InputValueEnum::Miss,
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d H:i:s')] public readonly \DateTimeImmutable|InputValueEnum $setDate = InputValueEnum::Miss,
        public readonly ReminderStatusEnum|InputValueEnum $status = InputValueEnum::Miss
    ) {
    }
}
