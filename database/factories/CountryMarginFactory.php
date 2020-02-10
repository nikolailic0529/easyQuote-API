<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Quote\Margin\CountryMargin;
use Faker\Generator as Faker;

$factory->define(CountryMargin::class, function (Faker $faker) {
    $vendor = app('vendor.repository')->random();
    $country = $vendor->countries->random();

    return [
        'quote_type'    => $faker->randomElement(['New', 'Renewal']),
        'method'        => 'No Margin',
        'is_fixed'      => false,
        'value'         => $faker->randomFloat(2, 1, 99),
        'country_id'    => $country->id,
        'vendor_id'     => $vendor->id
    ];
});
