<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use App\Domain\Date\Enum\DateDayEnum;
use App\Domain\Date\Enum\DateMonthEnum;
use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use Spatie\DataTransferObject\DataTransferObject;

final class CreateOpportunityRecurrenceData extends DataTransferObject
{
    public string $stage_id;

    public int $occur_every;
    public int $occurrences_count;
    public int $condition;

    public RecurrenceTypeEnum $type;

    public \DateTimeImmutable $start_date;
    public \DateTimeImmutable|null $end_date;

    public DateDayEnum $day;
    public DateMonthEnum $month;
    public int $day_of_week;
    public DateWeekEnum $week;
}
