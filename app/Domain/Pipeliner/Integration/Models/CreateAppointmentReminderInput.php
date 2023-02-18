<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Attributes\SerializeWith;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\ReminderStatusEnum;
use App\Domain\Pipeliner\Integration\Serializers\DateTimeSerializer;
use DateTimeInterface;

class CreateAppointmentReminderInput extends BaseInput
{
    public function __construct(
        public readonly string $ownerId,
        public readonly int $endDateOffset,
        #[SerializeWith(DateTimeSerializer::class, \DateTimeInterface::ATOM)] public readonly \DateTimeImmutable|InputValueEnum $snoozeDate = InputValueEnum::Miss,
        public readonly ReminderStatusEnum|InputValueEnum $status = InputValueEnum::Miss
    ) {
    }
}
