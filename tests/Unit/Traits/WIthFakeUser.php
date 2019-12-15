<?php

namespace Tests\Unit\Traits;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

trait WithFakeUser
{
    use WithFaker;

    /**
     * User instance.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * An Access Token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * Setup up the User instance.
     *
     * @return void
     */
    protected function setUpFakeUser()
    {
        $this->user = $this->createUser();
        $this->accessToken = $this->createAccessToken();
    }

    protected function createUser(): User
    {
        $user = User::create(
            [
                'email' => $this->faker->email,
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'password' => $this->faker->password,
                'country_id' => DB::table('countries')->value('id'),
                'timezone_id' => DB::table('timezones')->value('id'),
                'password_changed_at' => now(),
                'last_activity_at' => now()
            ]
        );

        $user->syncRoles(Role::administrator());

        return $user;
    }

    protected function createAccessToken(): string
    {
        auth()->login($this->user);

        return $this->user->createToken('Personal Access Token')->accessToken;
    }
}
