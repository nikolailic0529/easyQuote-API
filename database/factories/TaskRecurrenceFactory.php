<?php

namespace Database\Factories;

use App\Models\DateDay;
use App\Models\DateMonth;
use App\Models\DateWeek;
use App\Models\RecurrenceType;
use App\Models\Task\Task;
use App\Models\Task\TaskRecurrence;
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
