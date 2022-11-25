<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Task\TaskReminder;
use Faker\Generator as Faker;

$factory->define(TaskReminder::class, function (Faker $faker) {
    return [
        'task_id' => factory(\App\Models\Task\Task::class),
        'set_date' => now()->addDay(),
        'status' => $faker->randomElement(\App\Enum\ReminderStatus::cases()),
    ];
});
