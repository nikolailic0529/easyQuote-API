<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\QuoteFile\Models\ScheduleData;
use Faker\Generator as Faker;

$factory->define(ScheduleData::class, function (Faker $faker) {
    return [
        'value' => [
            [
                'from' => '01.01.2020',
                'to' => '30.01.2020',
                'price' => '10.000,00',
            ],
            [
                'from' => '31.01.2020',
                'to' => '30.01.2021',
                'price' => '12.000,00',
            ],
        ],
    ];
});
