<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Quote\WorldwideQuoteNote;
use Faker\Generator as Faker;

$factory->define(WorldwideQuoteNote::class, function (Faker $faker) {
    return [
        'text' => $faker->text()
    ];
});
