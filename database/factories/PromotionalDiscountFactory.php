<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Quote\Discount\PromotionalDiscount;
use Faker\Generator as Faker;

$factory->define(PromotionalDiscount::class, function (Faker $faker) {
    $vendor = app('vendor.repository')->random();
    $country = $vendor->load('countries')->countries->random();
    $value = number_format(rand(1, 99), 2, '.', '');
    $minimum_limit = rand(1, 3);

    return [
        'name'          => "PD {$country->code} {$value}",
        'country_id'    => $country->id,
        'vendor_id'     => $vendor->id,
        'value'         => $value,
        'minimum_limit' => $minimum_limit
    ];
});
