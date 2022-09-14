<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Enum\DateDayEnum;
use App\Integrations\Pipeliner\Enum\DateMonthEnum;
use App\Integrations\Pipeliner\Enum\DateWeekEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\RecurrenceTypeEnum;
use App\Integrations\Pipeliner\Serializers\DateTimeSerializer;
use DateTimeImmutable;

class UpdateTaskRecurrenceInput extends BaseInput
{
    public function __construct(
        public readonly string $id,
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d H:i:s')] public readonly DateTimeImmutable|InputValueEnum $startDate = InputValueEnum::Miss,
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d H:i:s')] public readonly DateTimeImmutable|null|InputValueEnum $endDate = InputValueEnum::Miss,
        public readonly RecurrenceTypeEnum|InputValueEnum $type = InputValueEnum::Miss,
        public readonly DateDayEnum|InputValueEnum $day = InputValueEnum::Miss,
        public readonly DateWeekEnum|InputValueEnum $week = InputValueEnum::Miss,
        public readonly DateMonthEnum|InputValueEnum $month = InputValueEnum::Miss,
        public readonly int|InputValueEnum $dayOfWeek = InputValueEnum::Miss,
        public readonly int|InputValueEnum $occurrencesCount = InputValueEnum::Miss,
        public readonly int|InputValueEnum $occurEvery = InputValueEnum::Miss,
        public readonly bool|InputValueEnum $applyRecurrence = InputValueEnum::Miss
    ) {
    }
}