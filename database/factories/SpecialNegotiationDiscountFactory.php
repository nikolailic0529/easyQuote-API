<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Discount\Models\SND;
use Faker\Generator as Faker;

$factory->define(SND::class, function (Faker $faker) {
    $vendor = app('vendor.repository')->random();
    $country = $vendor->countries->random();
    $value = number_format(rand(1, 99), 2, '.', '');

    return [
        'name' => "SN {$country->code} {$value}",
        'country_id' => $country->id,
        'vendor_id' => $vendor->id,
        'value' => $value,
    ];
});
