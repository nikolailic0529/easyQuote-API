<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Template\TemplateSchema;
use Faker\Generator as Faker;

$factory->define(TemplateSchema::class, function (Faker $faker) {
    return [
        'form_data' => [],
        'data_headers' => []
    ];
});
