<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Invitation\Models\Invitation;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Invitation::class, static function (Faker $faker): array {
    return [
        'email' => Str::uuid()->toString().'@example.com',
        'host' => config('app.url'),
    ];
});
