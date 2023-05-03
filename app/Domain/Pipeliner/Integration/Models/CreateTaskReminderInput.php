<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Attributes\SerializeWith;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\ReminderStatusEnum;
use App\Domain\Pipeliner\Integration\Serializers\DateTimeSerializer;
use DateTimeInterface;

class CreateTaskReminderInput extends BaseInput
{
    public function __construct(
        public readonly string $ownerId,
        #[SerializeWith(DateTimeSerializer::class, \DateTimeInterface::ATOM)] public readonly \DateTimeImmutable $setDate,
        public readonly ReminderStatusEnum|InputValueEnum $status = InputValueEnum::Miss
    ) {
    }
}
