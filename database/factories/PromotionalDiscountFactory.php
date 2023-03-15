<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Country\Models\Country;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(PromotionalDiscount::class, static function (Faker $faker): array {
    $country = Country::query()->first();
    /** @var Vendor $vendor */
    $vendor = factory(Vendor::class)->create();
    $vendor->countries()->sync($country);

    $value = number_format(rand(1, 99), 2, '.', '');
    $minimum_limit = rand(1, 3);

    return [
        'name' => "PD $country->code $value",
        'country_id' => $country->getKey(),
        'vendor_id' => $vendor->getKey(),
        'value' => $value,
        'minimum_limit' => $minimum_limit,
    ];
});
