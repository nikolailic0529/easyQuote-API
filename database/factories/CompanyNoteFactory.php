<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\CompanyNote;
use Faker\Generator as Faker;

$factory->define(CompanyNote::class, function (Faker $faker) {
    return [
        'text' => $faker->text(2000),
    ];
});
