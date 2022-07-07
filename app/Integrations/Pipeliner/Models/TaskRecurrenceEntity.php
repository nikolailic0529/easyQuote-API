<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\DateDayEnum;
use App\Integrations\Pipeliner\Enum\DateMonthEnum;
use App\Integrations\Pipeliner\Enum\DateWeekEnum;
use App\Integrations\Pipeliner\Enum\RecurrenceTypeEnum;
use Illuminate\Support\Carbon;

class TaskRecurrenceEntity
{
    public function __construct(public readonly string $id,
                                public readonly RecurrenceTypeEnum $type,
                                public readonly DateDayEnum $day,
                                public readonly DateWeekEnum $week,
                                public readonly DateMonthEnum $month,
                                public readonly int $dayOfWeek,
                                public readonly int $occurEvery,
                                public readonly int $occurrencesCount,
                                public readonly \DateTimeImmutable $startDate,
                                public readonly ?\DateTimeImmutable $endDate)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            type: RecurrenceTypeEnum::from($array['type']),
            day: DateDayEnum::from($array['day']),
            week: DateWeekEnum::from($array['week']),
            month: DateMonthEnum::from($array['month']),
            dayOfWeek: $array['dayOfWeek'],
            occurEvery: $array['occurEvery'],
            occurrencesCount: $array['occurrencesCount'],
            startDate: static::parseDateTime($array['startDate']),
            endDate: static::parseDateTime($array['endDate']),
        );
    }

    public static function tryFromArray(?array $array): ?static
    {
        if (is_null($array)) {
            return null;
        }

        return static::fromArray($array);
    }

    private static function parseDateTime(?string $dateTimeStr): ?\DateTimeImmutable
    {
        if (is_null($dateTimeStr)) {
            return null;
        }

        return Carbon::parse($dateTimeStr)->toDateTimeImmutable();
    }
}