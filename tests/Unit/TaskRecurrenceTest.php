<?php

namespace Tests\Unit;

use App\Domain\Date\Enum\DateDayEnum;
use App\Domain\Date\Enum\DateMonthEnum;
use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Date\Enum\DayOfWeekEnum;
use App\Domain\Date\Models\DateDay;
use App\Domain\Date\Models\DateMonth;
use App\Domain\Date\Models\DateWeek;
use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use App\Domain\Recurrence\Models\RecurrenceType;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskRecurrence;
use App\Domain\Task\Services\ProcessTaskRecurrenceService;
use Carbon\Carbon;
use Tests\TestCase;

class TaskRecurrenceTest extends TestCase
{
    /**
     * Test it checks whether the recurrence with Daily type can be performed.
     */
    public function testItChecksWhetherDailyRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-01'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::Daily)->sole());
        $recurrence->occur_every = 3;

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(1)->days(function () use ($service, $recurrence) {
            $this->assertFalse($service->isDue($recurrence));
        });
    }

    /**
     * Test it checks whether the recurrence with AfterNDays type can be performed.
     */
    public function testItChecksWhetherAfterNDaysRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::AfterNDays)->sole());
        $recurrence->occur_every = 10;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subDays(10);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $recurrence->task->expiry_date = now();

        $this->assertFalse($service->isDue($recurrence));
    }

    /**
     * Test it checks whether the recurrence with Weekly type can be performed.
     */
    public function testItChecksWhetherWeeklyRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::Weekly)->sole());
        $recurrence->occur_every = 2;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subDays(10);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(7)->days(function () use ($service, $recurrence) {
            $this->assertFalse($service->isDue($recurrence));
        });
    }

    /**
     * Test it checks whether the recurrence with AfterNWeeks type can be performed.
     */
    public function testItChecksWhetherAfterNWeeksRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::AfterNWeeks)->sole());
        $recurrence->occur_every = 3;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subWeeks(3);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(1)->day(function () use ($service, $recurrence) {
            $this->assertFalse($service->isDue($recurrence));
        });
    }

    /**
     * Test it checks whether the recurrence with MonthlyRelative type can be performed.
     */
    public function testItChecksWhetherMonthlyRelativeRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day()->associate(DateDay::query()->where('value', DateDayEnum::Day3)->sole());
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::AfterNWeeks)->sole());
        $recurrence->occur_every = 3;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subWeeks(3);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(1)->day(function () use ($service, $recurrence) {
            $this->assertFalse($service->isDue($recurrence));
        });
    }

    /**
     * Test it checks whether the recurrence with MonthlyAbsolute type can be performed.
     */
    public function testItChecksWhetherMonthlyAbsoluteRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day()->associate(DateDay::query()->where('value', DateDayEnum::Day3)->sole());
        $recurrence->month()->associate(DateMonth::query()->where('value', DateMonthEnum::Month1)->sole());
        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week2)->sole());
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::MonthlyAbsolute)->sole());
        $recurrence->occur_every = 3;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subWeeks(3);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week3)->sole());

        $this->assertFalse($service->isDue($recurrence));
    }

    /**
     * Test it checks whether the recurrence with AfterNMonths type can be performed.
     */
    public function testItChecksWhetherAfterNMonthsRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day()->associate(DateDay::query()->where('value', DateDayEnum::Day3)->sole());
        $recurrence->month()->associate(DateMonth::query()->where('value', DateMonthEnum::Month1)->sole());
        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week2)->sole());
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::AfterNMonths)->sole());
        $recurrence->occur_every = 3;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subMonths(3);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(1)->day();

        $this->assertFalse($service->isDue($recurrence));

        $this->travel(-2)->day();

        $this->assertFalse($service->isDue($recurrence));
    }

    /**
     * Test it checks whether the recurrence with YearlyRelative type can be performed.
     */
    public function testItChecksWhetherYearlyRelativeRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day()->associate(DateDay::query()->where('value', DateDayEnum::Day3)->sole());
        $recurrence->month()->associate(DateMonth::query()->where('value', DateMonthEnum::Month1)->sole());
        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week2)->sole());
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::YearlyRelative)->sole());
        $recurrence->occur_every = 2;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subMonths(3);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(1)->days();

        $this->assertFalse($service->isDue($recurrence));

        $this->travel(-2)->days();

        $this->assertFalse($service->isDue($recurrence));
    }

    /**
     * Test it checks whether the recurrence with YearlyAbsolute type can be performed.
     */
    public function testItChecksWhetherYearlyAbsoluteRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day()->associate(DateDay::query()->where('value', DateDayEnum::Day3)->sole());
        $recurrence->month()->associate(DateMonth::query()->where('value', DateMonthEnum::Month1)->sole());
        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week2)->sole());
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::YearlyAbsolute)->sole());
        $recurrence->occur_every = 2;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subMonths(3);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(1)->days();

        $this->assertFalse($service->isDue($recurrence));

        $this->travel(-2)->days();

        $this->assertFalse($service->isDue($recurrence));

        $this->travel(1)->days();

        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week1)->sole());

        $this->assertFalse($service->isDue($recurrence));

        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week2)->sole());
        $recurrence->month()->associate(DateMonth::query()->where('value', DateMonthEnum::Month2)->sole());

        $this->assertFalse($service->isDue($recurrence));

        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week2)->sole());
        $recurrence->month()->associate(DateMonth::query()->where('value', DateMonthEnum::Month1)->sole());
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value;

        $this->assertTrue($service->isDue($recurrence));

        $recurrence->day_of_week = DayOfWeekEnum::Tuesday->value;

        $this->assertFalse($service->isDue($recurrence));

        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->occur_every = 3;

        $this->travel(1)->months();

        $this->assertFalse($service->isDue($recurrence));
    }

    /**
     * Test it checks whether the recurrence with AfterNYears type can be performed.
     */
    public function testItChecksWhetherAfterNYearsRecurrenceCanBePerformed(): void
    {
        $this->travelTo(Carbon::parse('2022-01-03'));

        /** @var ProcessTaskRecurrenceService $service */
        $service = $this->app->make(ProcessTaskRecurrenceService::class);

        $recurrence = new TaskRecurrence();
        $recurrence->start_date = now();
        $recurrence->end_date = null;
        $recurrence->occurrences_count = -1;
        $recurrence->day()->associate(DateDay::query()->where('value', DateDayEnum::Day3)->sole());
        $recurrence->month()->associate(DateMonth::query()->where('value', DateMonthEnum::Month1)->sole());
        $recurrence->week()->associate(DateWeek::query()->where('value', DateWeekEnum::Week2)->sole());
        $recurrence->day_of_week = DayOfWeekEnum::Monday->value;
        $recurrence->type()->associate(RecurrenceType::query()->where('value', RecurrenceTypeEnum::AfterNYears)->sole());
        $recurrence->occur_every = 3;
        $recurrence->task()->associate(tap(new Task(), static function (Task $task) {
            $task->expiry_date = now()->subYears(3);
        }));

        $this->assertTrue($service->isDue($recurrence));

        $this->travel(1)->days();

        $this->assertFalse($service->isDue($recurrence));
    }
}
