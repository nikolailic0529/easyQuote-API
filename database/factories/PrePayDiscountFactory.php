<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Discount\Models\PrePayDiscount;
use Faker\Generator as Faker;

$factory->define(PrePayDiscount::class, function (Faker $faker) {
    $vendor = app('vendor.repository')->random();
    $country = $vendor->countries->random();
    $duration = rand(1, 3);
    $value = number_format(rand(1, 99), 2, '.', '');

    return [
        'name' => "PP {$country->code} {$value}",
        'country_id' => $country->id,
        'vendor_id' => $vendor->id,
        'durations' => [
            'duration' => compact('duration', 'value'),
        ],
    ];
});
