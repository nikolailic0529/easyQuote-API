<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(Vendor::class, function (Faker $faker) {
    return [
        'name' => $faker->company,
        'short_code' => $faker->regexify('/[A-Z0-9]{6}/'),
    ];
});

$factory->state(Vendor::class, 'countries', function () {
    return [
        'countries' => app('country.repository')->all()->take(4)->pluck('id')->toArray(),
    ];
});

$factory->afterCreating(Vendor::class, function (Vendor $vendor) {
    $countries = app('country.repository')->all()->take(4)->pluck('id')->toArray();
    $vendor->syncCountries($countries);
});
