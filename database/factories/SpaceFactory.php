<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Space\Models\Space;
use Faker\Generator as Faker;

$factory->define(Space::class, function (Faker $faker) {
    return [
        'space_name' => \Illuminate\Support\Str::random(40),
    ];
});
