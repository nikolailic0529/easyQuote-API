<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Customer\WorldwideCustomer;
use App\Models\Data\Country;
use Faker\Generator as Faker;

$factory->define(WorldwideCustomer::class, function (Faker $faker) {
    $country = Country::where('iso_3166_2', $faker->randomElement(['AT', 'GB', 'US', 'CH']))->first('id');

    return [
        'rfq_number'            => $faker->regexify('/WW[A-Z0-9]{20}/'),
        'support_start_date'    => now()->startOfDay()->toDateString(),
        'support_end_date'      => now()->addYears(2)->startOfDay()->toDateString(),
        'valid_until_date'      => now()->addYears(2)->startOfDay()->toDateString(),
        'customer_name'         => $faker->unique()->company,
        'invoicing_terms'       => $faker->randomElement([
            'Upfront',
            'Payment due immediately',
            '14 Days from Invoice Date',
            '20 Days End of Month',
            '30 Days from Invoice Date',
            '30 Days End of Month',
            '60 Days from Invoice Date',
            '60 Days from End of Month',
            'At the end of next month',
            'Payment before delivery'
        ]),
        'service_levels' => [
            "HPE Foundation Care NBD",
            "Foundation Care Next Business Day w/DMR",
            "Foundation Care Next Business Day",
            "Proactive Care Next Business Day w/DMR"
        ],
        'country_id'            => $country->getKey(),
    ];
});
