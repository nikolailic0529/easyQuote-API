<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\WorldwideQuoteAssetsGroup;
use Faker\Generator as Faker;

$factory->define(WorldwideQuoteAssetsGroup::class, function (Faker $faker) {
    return [
        'group_name' => $faker->text(200),
        'search_text' => $faker->text(200)
    ];
});
