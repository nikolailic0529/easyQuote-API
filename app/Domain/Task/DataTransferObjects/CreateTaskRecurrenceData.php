<?php

namespace App\Domain\Task\DataTransferObjects;

use App\Domain\Date\Enum\DateDayEnum;
use App\Domain\Date\Enum\DateMonthEnum;
use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use Spatie\DataTransferObject\DataTransferObject;

final class CreateTaskRecurrenceData extends DataTransferObject
{
    public int $occur_every;
    public int $occurrences_count;

    public RecurrenceTypeEnum $type;

    public \DateTimeImmutable $start_date;
    public \DateTimeImmutable|null $end_date;

    public DateDayEnum $day;
    public DateMonthEnum $month;
    public int $day_of_week;
    public DateWeekEnum $week;
}
