<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Address;
use Faker\Generator as Faker;

$factory->define(Address::class, function (Faker $faker) {
    return [
        'address_type' => 'Equipment',
        'address_1' => $faker->streetAddress,
        'city' => $faker->city,
        'post_code' => $faker->postcode,
        'state' => $faker->state,
        'state_code' => $faker->regexify('[A-Z]{2}'),
        'address_2' => $faker->streetAddress,
        'country_id' => \App\Models\Data\Country::value('id'),
        'contact_name' => $faker->name,
        'contact_number' => $faker->phoneNumber,
        'contact_email' => $faker->email,
    ];
});
