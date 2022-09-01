<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Customer\Customer;
use App\Models\Data\Country;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;

$factory->define(Customer::class, function (Faker $faker) {
    $country = Country::inRandomOrder()->first();

    return [
        'rfq' => $faker->regexify('/[A-Z0-9]{20}/'),
        'support_start' => now()->startOfDay(),
        'support_end' => now()->addYears(2)->startOfDay(),
        'valid_until' => now()->addYears(2)->startOfDay(),
        'name' => $faker->company,
        'invoicing_terms' => $faker->sentence,
        'country_id' => $country->id,
    ];
});

$factory->state(Customer::class, 'expired', [
    'valid_until' => now()->addDays(setting('notification_time')->d)
]);

$factory->state(Customer::class, 'request', function (Faker $faker) {
    $country = Country::inRandomOrder()->first();

    return [
        'rfq_number' => $faker->regexify('/[A-Z0-9]{20}/'),
        'support_start_date' => now()->startOfDay()->format('Y-m-d'),
        'support_end_date' => now()->addYears(2)->startOfDay()->format('Y-m-d'),
        'quotation_valid_until' => now()->addYears(2)->startOfDay()->format('m/d/Y'),
        'customer_name' => $faker->company,
        'invoicing_terms' => $faker->sentence,
        'country' => $country->code,
    ];
});

$factory->state(Customer::class, 'addresses', function (Faker $faker) {
    return [
        'addresses' => collect()->times(3)->map(function () {
            return [
                'address_type'      => Arr::random(['Equipment', 'Software']),
                'address_1'         => $this->faker->address,
                'address_2'         => $this->faker->address,
                'city'              => $this->faker->city,
                'state'             => $this->faker->state,
                'state_code'        => $this->faker->stateAbbr,
                'post_code'         => $this->faker->postcode,
                'country_code'      => $this->faker->countryCode,
                'contact_name'      => $this->faker->firstName,
                'contact_number'    => $this->faker->phoneNumber
            ];
        })->toArray()
    ];
});
