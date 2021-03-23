<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\OpportunitySupplier;
use Faker\Generator as Faker;

$factory->define(OpportunitySupplier::class, function (Faker $faker) {
    return [
        'supplier_name' => $faker->company,
        'country_name' => $faker->country,
        'contact_name' => $faker->firstName,
        'contact_email' => $faker->companyEmail,
    ];
});
