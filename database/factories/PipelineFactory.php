<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Pipeline\Pipeline;
use Faker\Generator as Faker;

$factory->define(Pipeline::class, function (Faker $faker) {
    return [
        'space_id' => SP_EPD,
        'pipeline_name' => \Illuminate\Support\Str::random(40),
    ];
});
