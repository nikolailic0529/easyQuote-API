<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Quote\QuoteNote;
use Faker\Generator as Faker;

$factory->define(QuoteNote::class, function (Faker $faker) {
    return [
        'text' => $faker->paragraphs(3, true)
    ];
});
