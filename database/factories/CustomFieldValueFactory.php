<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Model;
use App\Models\System\CustomFieldValue;
use Faker\Generator as Faker;

$factory->define(CustomFieldValue::class, function (Faker $faker) {
    return [
        'id' => null,
        'field_value' => Str::random(40)
    ];
});
