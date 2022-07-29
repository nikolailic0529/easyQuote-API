<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(WorldwideQuoteVersion::class, function (Faker $faker) {
    $user = User::factory()->create();

    return [
        'user_id' => $user->getKey(),
        'company_id' => Company::query()->where('short_code', $faker->randomElement(['SWH', 'EPD', 'THG']))->value('id'),
        'quote_currency_id' => Currency::query()->where('code', $faker->randomElement(['GBP', 'USD']))->value('id'),
    ];
});
