<?php

namespace Database\Factories;

use App\Domain\Date\Models\DateDay;
use App\Domain\Date\Models\DateMonth;
use App\Domain\Date\Models\DateWeek;
use App\Domain\Recurrence\Models\RecurrenceType;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskRecurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskRecurrenceFactory extends Factory
{
    protected $model = TaskRecurrence::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'type_id' => RecurrenceType::query()->inRandomOrder()->first(),
            'date_day_id' => DateDay::query()->inRandomOrder()->first(),
            'date_month_id' => DateMonth::query()->inRandomOrder()->first(),
            'date_week_id' => DateWeek::query()->inRandomOrder()->first(),
            'start_date' => now(),
        ];
    }
}
