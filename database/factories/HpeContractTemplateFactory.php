<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Template\HpeContractTemplate;
use Faker\Generator as Faker;

$factory->define(HpeContractTemplate::class, function (Faker $faker) {
    return [
        'name'         => $faker->words(3, true),
        'countries'   => app('country.repository')->all()->random(4)->pluck('id')->sort()->values()->toArray(),
        'company_id'  => app('company.repository')->random()->id,
        'vendor_id'   => app('vendor.repository')->random()->id,
        'currency_id' => app('currency.repository')->all()->random()->id,
        'form_data'   => []
    ];
});
