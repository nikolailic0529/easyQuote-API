<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Task\Models\TaskReminder;
use Faker\Generator as Faker;

$factory->define(TaskReminder::class, function (Faker $faker) {
    return [
        'task_id' => factory(\App\Domain\Task\Models\Task::class),
        'set_date' => now()->addDay(),
        'status' => $faker->randomElement(\App\Domain\Reminder\Enum\ReminderStatus::cases()),
    ];
});
