<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Country\Models\Country;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(PrePayDiscount::class, static function (Faker $faker): array {
    $country = Country::query()->first();
    /** @var Vendor $vendor */
    $vendor = factory(Vendor::class)->create();
    $vendor->countries()->sync($country);

    $duration = rand(1, 3);
    $value = number_format(rand(1, 99), 2, '.', '');

    return [
        'name' => "PP $country->code $value",
        'country_id' => $country->getKey(),
        'vendor_id' => $vendor->getKey(),
        'durations' => [
            'duration' => compact('duration', 'value'),
        ],
    ];
});
