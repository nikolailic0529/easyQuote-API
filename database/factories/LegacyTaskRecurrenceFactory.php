<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Task\TaskRecurrence;
use Faker\Generator as Faker;

$factory->define(TaskRecurrence::class, function (Faker $faker) {
    return [
        'task_id' => factory(\App\Models\Task\Task::class),
        'type_id' => \App\Models\RecurrenceType::query()->inRandomOrder()->first(),
        'date_day_id' => \App\Models\DateDay::query()->inRandomOrder()->first(),
        'date_month_id' => \App\Models\DateMonth::query()->inRandomOrder()->first(),
        'date_week_id' => \App\Models\DateWeek::query()->inRandomOrder()->first(),
        'start_date' => now(),
    ];
});
