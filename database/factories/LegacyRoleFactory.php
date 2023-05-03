<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Domain\Authorization\Models\Role;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

$factory->define(Role::class, function (Faker $faker) {
    return [
        'name' => Str::random(40),
        'guard_name' => 'api',
    ];
});

$factory->state(Role::class, 'privileges', function () {
    $modulePrivileges = collect(config('role.modules'))->eachKeys();
    $modules = array_keys(config('role.modules'));
    $submodules = config('role.submodules');

    $privileges = collect($modules)->map(function ($module) use ($modulePrivileges, $submodules) {
        $sub = collect($submodules[$module] ?? [])->map(function ($privileges, $subModuleName) {
            return ['submodule' => $subModuleName, 'privilege' => Arr::random(array_keys($privileges))];
        })->values()->toArray();

        return [
            'module' => $module,
            'privilege' => Arr::random($modulePrivileges[$module]),
            'submodules' => $sub,
        ];
    })->toArray();

    return [
        'privileges' => $privileges,
    ];
});
