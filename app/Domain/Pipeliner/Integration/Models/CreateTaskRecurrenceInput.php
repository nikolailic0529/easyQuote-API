<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Attributes\SerializeWith;
use App\Domain\Pipeliner\Integration\Enum\DateDayEnum;
use App\Domain\Pipeliner\Integration\Enum\DateMonthEnum;
use App\Domain\Pipeliner\Integration\Enum\DateWeekEnum;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\RecurrenceTypeEnum;
use App\Domain\Pipeliner\Integration\Serializers\DateTimeSerializer;

class CreateTaskRecurrenceInput extends BaseInput
{
    public function __construct(
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d')] public readonly \DateTimeImmutable $startDate,
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d')] public readonly \DateTimeImmutable|null|InputValueEnum $endDate = InputValueEnum::Miss,
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
