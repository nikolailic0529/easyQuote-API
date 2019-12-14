<?php

namespace Tests\Unit\Traits;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait FakeUser
{
    protected function fakeUser(): User
    {
        return User::create(
            [
                'email' => $this->faker->email,
                'password' => $this->faker->password,
                'country_id' => DB::table('countries')->value('id'),
                'timezone_id' => DB::table('timezones')->value('id'),
                'password_changed_at' => now()
            ]
        );
    }
}
