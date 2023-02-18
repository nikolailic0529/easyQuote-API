<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Team\Models\Team;
use Faker\Generator as Faker;

$factory->define(Team::class, function (Faker $faker) {
    return [
        'team_name' => $faker->text(191),
        'monthly_goal_amount' => null,
    ];
});
