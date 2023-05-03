<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Task\Models\TaskRecurrence;
use Faker\Generator as Faker;

$factory->define(TaskRecurrence::class, function (Faker $faker) {
    return [
        'task_id' => factory(\App\Domain\Task\Models\Task::class),
        'type_id' => \App\Domain\Recurrence\Models\RecurrenceType::query()->inRandomOrder()->first(),
        'date_day_id' => \App\Domain\Date\Models\DateDay::query()->inRandomOrder()->first(),
        'date_month_id' => \App\Domain\Date\Models\DateMonth::query()->inRandomOrder()->first(),
        'date_week_id' => \App\Domain\Date\Models\DateWeek::query()->inRandomOrder()->first(),
        'start_date' => now(),
    ];
});
