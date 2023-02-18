<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\{
    Currency
};
use Faker\Generator as Faker;

$factory->define(Country::class, function (Faker $faker) {
    do {
        $iso = $faker->regexify('[A-Z]{2}');
    } while (Country::where('iso_3166_2', $iso)->exists());

    return [
        'name' => $faker->country,
        'iso_3166_2' => $iso,
        'default_currency_id' => Currency::inRandomOrder()->value('id'),
        'currency_name' => $faker->currencyCode,
        'currency_code' => $faker->currencyCode,
        'currency_symbol' => $faker->randomLetter,
    ];
});
