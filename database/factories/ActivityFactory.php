<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use App\Models\System\Activity;

$factory->define(Activity::class, function (Faker $faker) {
    return [
        'log_name' => 'default',
        'description' => $faker->randomElement(['created', 'updated', 'deleted']),
        'subject_id' => $faker->uuid,
        'subject_type' => $faker->randomElement([
            \App\Models\User::class, \App\Models\Company::class, \App\Models\Vendor::class,
        ]),
        'causer_id' => $faker->uuid,
        'causer_type' => \App\Models\User::class,
        'properties' => [
            'old' => [],
            'attributes' => [
                'attr_1' => \Illuminate\Support\Str::random(),
                'attr_2' => \Illuminate\Support\Str::random()
            ]
        ]

    ];
});
