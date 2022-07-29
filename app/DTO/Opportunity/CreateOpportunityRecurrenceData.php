<?php

namespace App\DTO\Opportunity;

use App\Enum\DateDayEnum;
use App\Enum\DateMonthEnum;
use App\Enum\DateWeekEnum;
use App\Enum\RecurrenceTypeEnum;
use DateTimeImmutable;
use Spatie\DataTransferObject\DataTransferObject;

final class CreateOpportunityRecurrenceData extends DataTransferObject
{
    public string $stage_id;

    public int $occur_every;
    public int $occurrences_count;
    public int $condition;

    public RecurrenceTypeEnum $type;

    public DateTimeImmutable $start_date;
    public DateTimeImmutable|null $end_date;

    public DateDayEnum $day;
    public DateMonthEnum $month;
    public int $day_of_week;
    public DateWeekEnum $week;
}