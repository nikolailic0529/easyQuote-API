<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\HpeContract\Models\HpeContractTemplate;
use Faker\Generator as Faker;

$factory->define(HpeContractTemplate::class, function (Faker $faker) {
    return [
        'name' => $faker->words(3, true),
        'company_id' => \App\Domain\Company\Models\Company::value('id'),
        'vendor_id' => \App\Domain\Vendor\Models\Vendor::value('id'),
        'currency_id' => \App\Domain\Currency\Models\Currency::value('id'),
        'form_data' => [],
    ];
});
