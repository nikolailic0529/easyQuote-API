<?php

namespace Tests\Unit\User;

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
    }

    /**
     * Test Authentication of the existing user.
     *
     * @return void
     */
    public function testAuthExistingUser()
    {
        $attributes = ['email' => $this->faker->email, 'password' => 'password'];
        app('user.repository')->create($attributes);

        $response = $this->postJson(url('/api/auth/signin'), $attributes);

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
        $attributes = ['email' => $this->faker->email, 'password' => 'password'];
        app('user.repository')->create($attributes);

        data_set($attributes, 'password', Str::random(20));

        $response = $this->postJson(url('/api/auth/signin'), $attributes);

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

        $response = $this->postJson(url('/api/auth/signup'), $user);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }
}