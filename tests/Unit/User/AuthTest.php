<?php

namespace Tests\Unit\User;

use Tests\TestCase;
use Illuminate\Foundation\Testing\{
    WithFaker,
    DatabaseTransactions
};
use Str, Arr;

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
        $attributes = $this->makeGenericUserAttributes();
        app('user.repository')->create($attributes);

        $response = $this->postJson(url('/api/auth/signin'), Arr::only($attributes, ['email', 'password', 'local_ip']));

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }

    /**
     * Test Failed Authentication of the existing user.
     *
     * @return void
     */
    public function testFailingAuthExistingUser()
    {
        $attributes = $this->makeGenericUserAttributes();
        app('user.repository')->create($attributes);

        data_set($attributes, 'password', Str::random(20));

        $response = $this->postJson(url('/api/auth/signin'), Arr::only($attributes, ['email', 'password', 'local_ip']));

        $response->assertUnauthorized();
    }

    /**
     * Test Authentication of the existing user without passing ip address.
     *
     * @return void
     */
    public function testAuthUserWithoutLocalIp()
    {
        $attributes = $this->makeGenericUserAttributes();
        app('user.repository')->create($attributes);

        $response = $this->postJson(url('/api/auth/signin'), Arr::only($attributes, ['email', 'password']));

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('local_ip');
    }

    /**
     * User Registration Test.
     *
     * @return void
     */
    public function testSignupUser()
    {
        $attributes = $this->makeGenericUserAttributes();

        $response = $this->postJson(url('/api/auth/signup'), $attributes);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }

    protected function makeGenericUserAttributes(): array
    {
        $password = $this->faker->password;

        return [
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'password' => $password,
            'password_confirmation' => $password,
            'country_id' => \DB::table('countries')->value('id'),
            'timezone_id' => \DB::table('timezones')->value('id'),
            'local_ip' => $this->faker->ipv4
        ];
    }
}
