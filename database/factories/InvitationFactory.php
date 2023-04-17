<?php

namespace Database\Factories;

use App\Domain\Authorization\Models\Role;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_id' => Role::factory(),
            'email' => Str::uuid()->toString().'@example.com',
            'host' => config('app.url'),
        ];
    }
}