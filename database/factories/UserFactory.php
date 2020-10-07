<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'first_name'          => Str::filterLetters($this->faker->firstName),
        'last_name'           => Str::filterLetters($this->faker->lastName),
        'email'               => $faker->unique()->safeEmail,
        'email_verified_at'   => now(),
        'password'            => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'timezone_id'         => DB::table('timezones')->value('id'),
        'remember_token'      => Str::random(10),
        'password_changed_at' => now(),
        'last_activity_at'    => now(),
        'ip_address'          => $this->faker->ipv4,
        'already_logged_in'   => 1,
    ];
});

$factory->afterCreating(User::class, function (User $user, Faker $faker) {
    $user->syncRoles('Administrator');
});
