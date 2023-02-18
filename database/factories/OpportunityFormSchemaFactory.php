<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Worldwide\Models\OpportunityFormSchema;
use Faker\Generator as Faker;

$factory->define(OpportunityFormSchema::class, function (Faker $faker) {
    return [
        'form_data' => [],
    ];
});
