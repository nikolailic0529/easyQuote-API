<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use Faker\Generator as Faker;

$factory->define(Company::class, function (Faker $faker) {
    return [
        'name' => $faker->company,
        'short_code' => $faker->unique()->regexify('/[A-Z]{3}/'),
        'vat' => $faker->unique()->bankAccountNumber,
        'vat_type' => 'VAT Number',
        'type' => 'Internal',
        'email' => $this->faker->unique()->companyEmail,
        'phone' => $this->faker->phoneNumber,
        'website' => $this->faker->url,
    ];
});
