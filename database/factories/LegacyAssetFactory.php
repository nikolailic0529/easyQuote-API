<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Address\Models\Address;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\AssetCategory;
use App\Domain\Vendor\Models\Vendor;
use Faker\Generator as Faker;

$factory->define(Asset::class, function (Faker $faker) {
    $vendor = Vendor::first();
    $category = AssetCategory::first();
    $address = factory(Address::class)->create();

    return [
        'serial_number' => $faker->regexify('[A-Z0-9]{6}'),
        'product_number' => $faker->regexify('\d{4}[A-Z]{4}'),
        'product_description' => $faker->text(191),
        'unit_price' => (float) mt_rand(10, 10000),
        'vendor_id' => optional($vendor)->getKey(),
        'asset_category_id' => optional($category)->getKey(),
        'address_id' => optional($address)->getKey(),
        'vendor_short_code' => optional($vendor)->short_code,
        'base_warranty_start_date' => now()->format('Y-m-d'),
        'base_warranty_end_date' => now()->addYears(mt_rand(1, 10))->format('Y-m-d'),
        'active_warranty_start_date' => now()->format('Y-m-d'),
        'active_warranty_end_date' => now()->addYears(mt_rand(1, 10))->format('Y-m-d'),
    ];
});
