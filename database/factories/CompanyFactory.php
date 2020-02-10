<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use Faker\Generator as Faker;

$factory->define(Company::class, function (Faker $faker) {
    return [
        'name'      => $faker->company,
        'vat'       => $faker->unique()->bankAccountNumber,
        'type'      => 'Internal',
        'email'     => $this->faker->unique()->companyEmail,
        'phone'     => $this->faker->phoneNumber,
        'website'   => $this->faker->url,
        'vendors'   => app('vendor.repository')->random(2)->pluck('id')->toArray()
    ];
});
