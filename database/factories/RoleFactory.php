<?php

namespace Database\Factories;

use App\Domain\Authorization\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => Str::random(20),
            'guard_name' => 'web',
        ];
    }
}
