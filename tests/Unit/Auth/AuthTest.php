<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\{
    WithFaker,
    DatabaseTransactions
};
use Str;

class AuthTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Predefined Users.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $users;

    public function setUp(): void
    {
        parent::setUp();

        $this->users = collect(json_decode(file_get_contents(database_path('seeds/models/users.json')), true));
    }

    /**
     * Test Authentication of the existing user.
     *
     * @return void
     */
    public function testAuthExistingUser()
    {
        $response = $this->postJson('/api/auth/signin', $this->users->random());

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }

    /**
     * Test Failing Authentication of the existing user.
     *
     * @return void
     */
    public function testFailingAuthExistingUser()
    {
        $user = $this->users->random();
        data_set($user, 'password', Str::random(20));

        $response = $this->postJson('/api/auth/signin', $user);

        $response->assertUnauthorized();
    }

    /**
     * User Registration Test.
     *
     * @return void
     */
    public function testSignupUser()
    {
        $password = $this->faker->password;

        $user = [
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'password' => $password,
            'password_confirmation' => $password,
            'country_id' => \DB::table('countries')->value('id'),
            'timezone_id' => \DB::table('timezones')->value('id')
        ];

        $response = $this->postJson('/api/auth/signup', $user);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }
}
