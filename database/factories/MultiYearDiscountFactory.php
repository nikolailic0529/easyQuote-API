<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Quote\Discount\MultiYearDiscount;
use Faker\Generator as Faker;

$factory->define(MultiYearDiscount::class, function (Faker $faker) {
    $vendor = app('vendor.repository')->random();
    $country = $vendor->countries->random();
    $duration = rand(1, 5);
    $value = number_format(rand(1, 99), 2, '.', '');

    return [
        'name'          => "MY {$country->code} {$value}",
        'country_id'    => $country->id,
        'vendor_id'     => $vendor->id,
        'durations'     => [
            'duration'  => compact('duration', 'value')
        ]
    ];
});
