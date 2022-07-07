<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Company;
use App\Models\Customer\WorldwideCustomer;
use App\Models\Data\Currency;
use App\Models\Opportunity;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(WorldwideQuote::class, function (Faker $faker) {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create();

    $sequenceNumber = \Illuminate\Support\Facades\DB::table('worldwide_quotes')->max('sequence_number');
    $newNumber = $sequenceNumber + 1;

    $activeVersion = factory(WorldwideQuoteVersion::class)->create();

    return [
        'active_version_id' => $activeVersion->getKey(),
        'contract_type_id' => CT_CONTRACT,
        'user_id' => $user->getKey(),
        'opportunity_id' => $opportunity->getKey(),
        'sequence_number' => $newNumber,
        'quote_number' => sprintf("EPD-WW-DP-%'.07d", $newNumber)
    ];
});

$factory->afterCreating(WorldwideQuote::class, function (WorldwideQuote $worldwideQuote) {
    $worldwideQuote->activeVersion->worldwideQuote()->associate($worldwideQuote);
    $worldwideQuote->activeVersion->save();
});
