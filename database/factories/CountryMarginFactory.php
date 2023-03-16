<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Country\Models\Country;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(CountryMargin::class, static function (Faker $faker): array {
    $country = Country::query()->first();
    /** @var Vendor $vendor */
    $vendor = factory(Vendor::class)->create();
    $vendor->countries()->sync($country);

    return [
        'quote_type' => $faker->randomElement(['New', 'Renewal']),
        'method' => 'No Margin',
        'is_fixed' => false,
        'value' => $faker->randomFloat(2, 1, 99),
        'country_id' => $country->getKey(),
        'vendor_id' => $vendor->getKey(),
    ];
});
