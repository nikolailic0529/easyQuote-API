<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Data\Country;
use App\Models\QuoteFile\ImportableColumn;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(ImportableColumn::class, function (Faker $faker) {
    $country = Country::query()->first();

    return [
        'header' => Str::random(),
        'country_id' => $country->getKey(),
        'type' => $faker->randomElement(['text', 'date', 'number', 'decimal'])
    ];
});

$factory->state(ImportableColumn::class, 'aliases', function (Faker $faker) {
    return [
        'aliases' => array_unique($faker->words(10))
    ];
});
