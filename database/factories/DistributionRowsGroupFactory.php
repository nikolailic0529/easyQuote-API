<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Worldwide\Models\DistributionRowsGroup;
use Faker\Generator as Faker;

$factory->define(DistributionRowsGroup::class, function (Faker $faker) {
    return [
        'group_name' => $faker->text(200),
        'search_text' => $faker->text(200),
    ];
});
