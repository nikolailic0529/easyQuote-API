<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Faker\Generator as Faker;

$factory->define(WorldwideDistribution::class, function (Faker $faker) {
    /** @var \App\Domain\Worldwide\Models\WorldwideQuote $wwQuote */
    $wwQuote = factory(WorldwideQuote::class)->create();

    return [
        'worldwide_quote_id' => $wwQuote->activeVersion->getKey(),
        'worldwide_quote_type' => $wwQuote->activeVersion->getMorphClass(),
    ];
});
