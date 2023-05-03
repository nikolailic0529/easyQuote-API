<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Country\Models\Country;
use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(Vendor::class, static function (Faker $faker): array {
    return [
        'name' => $faker->company,
        'short_code' => $faker->regexify('/[A-Z0-9]{6}/'),
    ];
});

$factory->state(Vendor::class, 'countries', static function () {
    return [
        'countries' => Country::query()->take(4)->pluck('id')->all(),
    ];
});

$factory->afterCreating(Vendor::class, static function (Vendor $vendor): void {
    $countries = Country::query()->take(4)->pluck('id')->all();
    $vendor->syncCountries($countries);
});
