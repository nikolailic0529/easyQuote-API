<?php

namespace Tests\Unit\User;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Str, Arr;

class AuthTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser;

    /** @var boolean */
    protected $dontAuthenticate = true;

    /** @var array */
    protected static $assertableAttributes = ['email', 'password', 'local_ip'];

    /**
     * Test Authentication of the existing user.
     *
     * @return void
     */
    public function testAuthExistingUser()
    {
        $attributes = factory(User::class, 'authentication')->raw();

        $response = $this->postJson(url('/api/auth/signin'), $attributes);

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
        $attributes = factory(User::class, 'authentication')->raw();

        data_set($attributes, 'password', Str::random(20));

        $response = $this->postJson(url('/api/auth/signin'), $attributes);

        $response->assertStatus(403);
    }

    /**
     * Test Authentication of the existing user without passing ip address.
     *
     * @return void
     */
    public function testAuthUserWithoutLocalIp()
    {
        $attributes = factory(User::class, 'authentication')->raw();

        $response = $this->postJson(url('/api/auth/signin'), Arr::only($attributes, ['email', 'password']));

        $response->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['local_ip']]
            ]);
    }

    /**
     * User Registration Test.
     *
     * @return void
     */
    public function testSignupUser()
    {
        $attributes = factory(User::class, 'registration')->raw();

        $response = $this->postJson(url('/api/auth/signup'), $attributes);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }

    /**
     * Test User logout due inactivity.
     *
     * @return void
     */
    public function testLogoutDueInactivity()
    {
        $this->user->setLastActivityAt(now()->subHour());

        $response = $this->authenticate()->getJson(url('api/auth/user'));

        $this->assertFalse(
            $this->user->tokens()->where('revoked', false)->exists()
        );

        $response->assertUnauthorized()
            ->assertExactJson([
                'ErrorCode' => 'LO_00',
                'ErrorDetails' => LO_00,
                'message' => LO_00
            ]);
    }

    /**
     * Test retrieving Authenticated User.
     *
     * @return void
     */
    public function testCurrentUserRetrieving()
    {
        $response = $this->authenticate()->getJson(url('api/auth/user'));

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'email', 'first_name', 'middle_name', 'last_name', 'default_route', 'already_logged_in', 'role_id', 'role_name', 'privileges'
            ]);
    }
}
