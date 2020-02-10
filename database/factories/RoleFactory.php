<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Role;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Role::class, function (Faker $faker) {
    $modulePrivileges = collect(config('role.modules'))->eachKeys();
    $modules = array_keys(config('role.modules'));

    $privileges = collect($modules)->transform(function ($module) use ($modulePrivileges) {
        $privilege = collect($modulePrivileges->get($module))->random();
        return compact('module', 'privilege');
    })->toArray();

    return [
        'name'          => Str::random(40),
        'privileges'    => $privileges,
        'guard_name'    => 'web'
    ];
});
