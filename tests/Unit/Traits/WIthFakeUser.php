<?php

namespace Tests\Unit\Traits;

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
     * Authorization Header for Request.
     *
     * @var array
     */
    protected $authorizationHeader;

    /**
     * Setup up the User instance.
     *
     * @return void
     */
    protected function setUpFakeUser()
    {
        $this->user = $this->createUser();
        $this->accessToken = $this->createAccessToken();
        $this->authorizationHeader = ['Authorization' => "Bearer {$this->accessToken}"];
    }

    protected function createUser(): User
    {
        $user = app('user.repository')->create(
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

        $user->syncRoles('Administrator');

        return $user;
    }

    protected function createAccessToken(?User $user = null): string
    {
        $user ??= $this->user;

        return $user->createToken('Personal Access Token')->accessToken;
    }
}
