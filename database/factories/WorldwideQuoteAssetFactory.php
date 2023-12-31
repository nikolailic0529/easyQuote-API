<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use Faker\Generator as Faker;

$factory->define(WorldwideQuoteAsset::class, function (Faker $faker) {
    $address = factory(\App\Domain\Address\Models\Address::class)->create();
    $vendor = \App\Domain\Vendor\Models\Vendor::query()->whereIn('short_code', ['HPE', 'LEN'])->first();
    $currency = \App\Domain\Currency\Models\Currency::query()->where('code', 'GBP')->first();

    return [
        'buy_currency_id' => $currency->getKey(),
        'vendor_id' => $vendor->getKey(),
        'machine_address_id' => $address->getKey(),
        'country' => $faker->countryCode,
        'serial_no' => $faker->regexify('[A-Z0-9]{6}'),
        'product_name' => $faker->regexify('\d{4}[A-Z]{4}'),
        'service_level_description' => $faker->text(100),
        'sku' => $faker->regexify('\d{4}[A-Z]{4}'),
        'service_sku' => $faker->regexify('\d{4}[A-Z]{4}'),
        'price' => (float) mt_rand(10, 10000),
        'buy_price' => (float) mt_rand(10, 10000),
        'buy_price_margin' => (float) mt_rand(0, 100),
        'expiry_date' => now()->addYears(mt_rand(1, 10))->format('Y-m-d'),
    ];
});
