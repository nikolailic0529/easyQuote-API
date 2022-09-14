<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\ReminderStatusEnum;
use App\Integrations\Pipeliner\Serializers\DateTimeSerializer;
use DateTimeImmutable;

class CreateAppointmentReminderInput extends BaseInput
{
    public function __construct(
        public readonly string $ownerId,
        public readonly int $endDateOffset,
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d H:i:s')] public readonly DateTimeImmutable|InputValueEnum $snoozeDate = InputValueEnum::Miss,
        public readonly ReminderStatusEnum|InputValueEnum $status = InputValueEnum::Miss
    ) {
    }
}