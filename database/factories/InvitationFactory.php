<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Invitation\Models\Invitation;
use Faker\Generator as Faker;

$factory->define(Invitation::class, function (Faker $faker) {
    return [
        'email' => $faker->unique()->safeEmail,
        'host' => config('app.url'),
    ];
});
