<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use App\Models\Customer\WorldwideCustomer;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(WorldwideQuote::class, function (Faker $faker) {
    $user = factory(User::class)->create();
    $opportunity = factory(\App\Models\Opportunity::class)->create();

    $sequenceNumber = \Illuminate\Support\Facades\DB::table('worldwide_quotes')->max('sequence_number');
    $newNumber = $sequenceNumber + 1;

    return [
        'contract_type_id' => CT_CONTRACT,
        'user_id' => $user->getKey(),
        'company_id' => Company::where('short_code', $faker->randomElement(['SWH', 'EPD', 'THG']))->value('id'),
        'opportunity_id' => $opportunity->getKey(),
        'quote_currency_id' => Currency::where('code', $faker->randomElement(['GBP', 'USD']))->value('id'),
        'sequence_number' => $newNumber,
        'quote_number' => sprintf("EPD-WW-DP-%'.07d", $newNumber)
    ];
});
