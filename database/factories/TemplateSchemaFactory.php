<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Template\Models\TemplateSchema;
use Faker\Generator as Faker;

$factory->define(TemplateSchema::class, function (Faker $faker) {
    return [
        'form_data' => [],
        'data_headers' => [],
    ];
});
