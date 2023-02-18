<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Country\Models\Country;
use App\Domain\QuoteFile\Models\ImportableColumn;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(ImportableColumn::class, function (Faker $faker) {
    $country = Country::query()->first();

    return [
        'header' => Str::random(),
        'country_id' => $country->getKey(),
        'type' => $faker->randomElement(['text', 'date', 'number', 'decimal']),
    ];
});

$factory->state(ImportableColumn::class, 'aliases', function (Faker $faker) {
    return [
        'aliases' => collect()->times(10, fn () => Str::random(20))->all(),
    ];
});
