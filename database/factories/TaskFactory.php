<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\{
    Attachment,
    Task,
    User,
};
use Faker\Generator as Faker;

$factory->define(Task::class, function (Faker $faker) {
    $user = User::firstOr(fn () => factory(User::class)->create());

    return [
        'user_id'       => $user->id,
        'name'          => $faker->text(50),
        'content'       => [
            [
                'id' => $faker->uuid,
                'name' => $faker->randomElement(['Single Column', 'Two Column', 'Three Column']),
                'child' => [],
                'class' => 'single-column field-dragger',
                'order' => 1,
                'controls' => [],
                'is_field' => false,
                'droppable' => false,
                'decoration' => '1'
            ]
        ],
        'expiry_date'   => now()->addYear(mt_rand(1, 2))->format('Y-m-d H:i:s'),
        'priority'      => mt_rand(1, 3)
    ];
});

$factory->state(Task::class, 'expired', [
    'expiry_date' => now()->subDay()
]);

$factory->afterCreating(Task::class, function (Task $task) {
    $task->users()->sync(factory(User::class, 2)->create());
    $task->attachments()->sync(factory(Attachment::class, 2)->create());
});

$factory->state(Task::class, 'users', function (Faker $faker) {
    return [
        'users' => factory(User::class, 2)->create()->pluck('id')->toArray()
    ];
});

$factory->state(Task::class, 'attachments', function (Faker $faker) {
    return [
        'attachments' => factory(Attachment::class, 4)->create()->pluck('id')->toArray()
    ];
});
