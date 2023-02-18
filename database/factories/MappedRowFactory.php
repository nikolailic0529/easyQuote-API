<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\DocumentMapping\Models\MappedRow;
use Faker\Generator as Faker;

$factory->define(MappedRow::class, function (Faker $faker) {
    return [
        'product_no' => $faker->regexify('/\d{6}-[A-Z]\d{2}/'),
        'serial_no' => $faker->regexify('/[A-Z]{2}\d{4}[A-Z]{2}[A-Z]/'),
        'service_sku' => $faker->regexify('/\d{6}-[A-Z]\d{2}/'),
        'description' => $faker->text(maxNbChars: 100),
        'date_from' => now(),
        'date_to' => now()->addYears(2),
        'qty' => 1,
        'price' => 1192.00,
        'machine_address_id' => factory(\App\Domain\Address\Models\Address::class),
    ];
});
