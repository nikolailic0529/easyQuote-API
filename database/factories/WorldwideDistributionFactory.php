<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Relations\Relation;

$factory->define(WorldwideDistribution::class, function (Faker $faker) {
    $wwQuote = factory(WorldwideQuote::class)->create();

    return [
        'worldwide_quote_id' => $wwQuote->getKey(),
        'worldwide_quote_type' => $wwQuote->getMorphClass(),
    ];
});
