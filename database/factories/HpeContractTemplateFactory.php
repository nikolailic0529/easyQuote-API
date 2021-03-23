<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Template\HpeContractTemplate;
use Faker\Generator as Faker;

$factory->define(HpeContractTemplate::class, function (Faker $faker) {
    return [
        'name' => $faker->words(3, true),
        'company_id' => \App\Models\Company::value('id'),
        'vendor_id' => \App\Models\Vendor::value('id'),
        'currency_id' => \App\Models\Data\Currency::value('id'),
        'form_data' => [],
    ];
});
