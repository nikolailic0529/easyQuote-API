<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Contact;
use Faker\Generator as Faker;

$factory->define(Contact::class, function (Faker $faker) {
    return [
        'contact_type' => $faker->randomElement(['Hardware', 'Software']),
        'contact_name' => $faker->name,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'mobile' => $faker->e164PhoneNumber,
        'phone' => $faker->e164PhoneNumber,
        'email' => $faker->companyEmail,
    ];
});
