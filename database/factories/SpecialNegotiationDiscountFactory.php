<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Country\Models\Country;
use App\Domain\Discount\Models\SND;
use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(SND::class, static function (Faker $faker): array {
    $country = Country::query()->first();
    /** @var Vendor $vendor */
    $vendor = factory(Vendor::class)->create();
    $vendor->countries()->sync($country);

    $value = number_format(rand(1, 99), 2, '.', '');

    return [
        'name' => "SN $country->code $value",
        'country_id' => $country->getKey(),
        'vendor_id' => $vendor->getKey(),
        'value' => $value,
    ];
});
