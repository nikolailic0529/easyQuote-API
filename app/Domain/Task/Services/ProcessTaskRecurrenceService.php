<?php

namespace App\Domain\Task\Services;

use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Date\Enum\DayOfWeekEnum;
use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskRecurrence;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProcessTaskRecurrenceService implements LoggerAware
{
    public function __construct(protected ConnectionInterface $connection,
                                protected LoggerInterface $logger = new NullLogger())
    {
    }

    public function process(): void
    {
        $this->logger->info('Performing task recurrences...');

        foreach (\App\Domain\Task\Models\TaskRecurrence::query()->cursor() as $recurrence) {
            $this->processRecurrence($recurrence);
        }
    }

    public function processRecurrence(TaskRecurrence $recurrence): void
    {
        if (false === $this->isDue($recurrence)) {
            return;
        }

        $this->logger->info('Processing task recurrence.', [
            'recurrence_id' => $recurrence->getKey(),
            'task_id' => $recurrence->task()->getParentKey(),
        ]);

        // Decrement occurrences count when set
        if ($recurrence->occurrences_count > 0) {
            --$recurrence->occurrences_count;
        }

        /** @var \App\Domain\Task\Models\Task $sourceTask */
        $sourceTask = $recurrence->task;

        /** @var \App\Domain\Task\Models\Task $replicatedTask */
        $replicatedTask = tap($sourceTask->replicate(), static function (Task $replicatedTask) use ($sourceTask): void {
            $replicatedTask->user()->associate($sourceTask->user);
        });

        $userRelations = $sourceTask->users;
        $attachmentRelations = $sourceTask->attachments;

        $this->connection->transaction(static function () use ($attachmentRelations, $replicatedTask, $userRelations, $recurrence) {
            $recurrence->save();
            $replicatedTask->save();

            $replicatedTask->users()->sync($userRelations);
            $replicatedTask->attachments()->sync($attachmentRelations);
        });

        $this->logger->info('Repeated the task.', [
            'recurrence_id' => $recurrence->getKey(),
            'task_id' => $recurrence->task()->getParentKey(),
            'new_task_id' => $replicatedTask->getKey(),
        ]);
    }

    public function isDue(TaskRecurrence $recurrence): bool
    {
        $date = Date::now();

        if (Carbon::instance($date)->lessThan($recurrence->start_date)) {
            return false;
        }

        if (null !== $recurrence->end_date && Carbon::instance($date)->greaterThanOrEqualTo($recurrence->end_date)) {
            return false;
        }

        if (is_null($recurrence->end_date) && 0 === $recurrence->occurrences_count) {
            return false;
        }

        return match ($recurrence->type->toEnum()) {
            RecurrenceTypeEnum::Daily => value(function () use ($date, $recurrence): bool {
                return $this->isInIncrementsOfRanges($date->day, $recurrence->occur_every, 31);
            }),
            RecurrenceTypeEnum::AfterNDays => value(function () use ($date, $recurrence): bool {
                return Carbon::instance($date)->isSameDay(
                    Carbon::instance($recurrence->task->expiry_date)->addDays($recurrence->occur_every)
                );
            }),
            RecurrenceTypeEnum::Weekly => value(function () use ($date, $recurrence): bool {
                return $this->isInSetDaysOfWeek($date->dayOfWeek, $recurrence->day_of_week)
                    && $this->isInIncrementsOfRanges($date->weekOfYear, $recurrence->occur_every, $date->weeksInYear);
            }),
            RecurrenceTypeEnum::AfterNWeeks => value(function () use ($date, $recurrence): bool {
                return Carbon::instance($date)->isSameDay(
                    Carbon::instance($recurrence->task->expiry_date)->addWeeks($recurrence->occur_every)
                );
            }),
            RecurrenceTypeEnum::MonthlyRelative => value(function () use ($date, $recurrence): bool {
                return Carbon::instance($date)->day === $recurrence->day->toDayNumber()
                    && $this->isInIncrementsOfRanges($date->month, $recurrence->occur_every, 12);
            }),
            RecurrenceTypeEnum::MonthlyAbsolute => value(function () use ($date, $recurrence): bool {
                $date = Carbon::instance($date);

                $isDueWeek = match ($recurrence->week->toEnum()) {
                    DateWeekEnum::Week1 => 1 === $date->weekNumberInMonth,
                    DateWeekEnum::Week2 => 2 === $date->weekNumberInMonth,
                    DateWeekEnum::Week3 => 3 === $date->weekNumberInMonth,
                    DateWeekEnum::Week4 => 4 === $date->weekNumberInMonth,
                    DateWeekEnum::WeekLast => Carbon::instance($date)->endOfMonth()->isSameWeek($date),
                };

                return $isDueWeek
                    && $this->isInSetDaysOfWeek($date->dayOfWeek, $recurrence->day_of_week)
                    && $this->isInIncrementsOfRanges($date->month, $recurrence->occur_every, 12);
            }),
            RecurrenceTypeEnum::AfterNMonths => value(function () use ($date, $recurrence): bool {
                return Carbon::instance($date)->isSameDay(
                    Carbon::instance($recurrence->task->expiry_date)->addMonths($recurrence->occur_every)
                );
            }),
            RecurrenceTypeEnum::YearlyRelative => value(function () use ($date, $recurrence): bool {
                return Carbon::instance($date)->month === $recurrence->month->toEnum()->toMonthNumber()
                    && $this->isInIncrementsOfRanges($date->day, $recurrence->occur_every, 1, 31);
            }),
            RecurrenceTypeEnum::YearlyAbsolute => value(function () use ($date, $recurrence): bool {
                $date = Carbon::instance($date);

                $isDueWeek = match ($recurrence->week->toEnum()) {
                    DateWeekEnum::Week1 => 1 === $date->weekNumberInMonth,
                    DateWeekEnum::Week2 => 2 === $date->weekNumberInMonth,
                    DateWeekEnum::Week3 => 3 === $date->weekNumberInMonth,
                    DateWeekEnum::Week4 => 4 === $date->weekNumberInMonth,
                    DateWeekEnum::WeekLast => Carbon::instance($date)->endOfMonth()->isSameWeek($date),
                };

                return $isDueWeek
                    && $date->month === $recurrence->month->toMonthNumber()
                    && $this->isInSetDaysOfWeek($date->dayOfWeek, $recurrence->day_of_week)
                    && $this->isInIncrementsOfRanges($date->month, $recurrence->occur_every, 12);
            }),
            RecurrenceTypeEnum::AfterNYears => value(function () use ($date, $recurrence): bool {
                return Carbon::instance($date)->isSameDay(
                    Carbon::instance($recurrence->task->expiry_date)->addYears($recurrence->occur_every)
                );
            }),
        };
    }

    protected function isInSetDaysOfWeek(int $dateValue, int $dayOfWeeks): bool
    {
        foreach (DayOfWeekEnum::cases() as $case) {
            if ($case->toDayOfWeekNumber() === $dateValue && $case->isPresentInMask($dayOfWeeks)) {
                return true;
            }
        }

        return false;
    }

    protected function isInIncrementsOfRanges(int $dateValue, int $step, int $rangeStart, int $rangeEnd = null): bool
    {
        if (is_null($rangeEnd)) {
            $rangeEnd = $rangeStart;
            $rangeStart = 1;
        }

        $fullRange = range($rangeStart, $rangeEnd);

        if ($step > $rangeEnd) {
            $thisRange = [$fullRange[$step % count($fullRange)]];
        } else {
            if ($step > ($rangeEnd - $rangeStart)) {
                $thisRange[$rangeStart] = $rangeStart;
            } else {
                $thisRange = range($rangeStart, $rangeEnd, $step);
            }
        }

        return in_array($dateValue, $thisRange, true);
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn () => $this->logger = $logger);
    }
}
