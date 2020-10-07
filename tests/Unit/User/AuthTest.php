<?php

namespace Tests\Unit\User;

use App\Http\Middleware\PerformUserActivity;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Support\{Str, Arr};

class AuthTest extends TestCase
{
    use WithFakeUser;

    protected bool $dontAuthenticate = true;

    protected static array $assertableAttributes = ['email', 'password', 'local_ip'];

    /**
     * Test Authentication of the existing user.
     *
     * @return void
     */
    public function testAuthExistingUser()
    {
        $user = factory(User::class)->create();

        $attributes = [
            'email'       => $user->email,
            'local_ip'    => $user->ip_address,
            'password'    => 'password',
            'g_recaptcha' => Str::random(),
        ];

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
        $user = factory(User::class)->create();

        $attributes = [
            'email'       => $user->email,
            'local_ip'    => $user->ip_address,
            'password'    => Str::random(20),
            'g_recaptcha' => Str::random(),
        ];

        $this->postJson(url('/api/auth/signin'), $attributes)->assertStatus(403);
    }

    /**
     * Test authentication of deactivated user.
     *
     * @return void
     */
    public function testAuthDeactivatedUser()
    {
        $user = tap(factory(User::class)->create())->deactivate();

        $credentials = ['email' => $user->email, 'password' => 'password', 'local_ip' => '192.168.99.99', 'g_recaptcha' => Str::random()];

        $response = $this->postJson(url('/api/auth/signin'), $credentials)->assertStatus(422);

        $this->assertTrue(Str::contains($response->json('message'), 'user is blocked'));
    }

    /**
     * Test Authentication of the existing user without passing ip address.
     *
     * @return void
     */
    public function testAuthUserWithoutLocalIp()
    {
        $user = factory(User::class)->create();

        $attributes = [
            'email'       => $user->email,
            'local_ip'    => $user->ip_address,
            'password'    => 'password',
            'g_recaptcha' => Str::random(),
        ];

        $this->postJson(url('/api/auth/signin'), Arr::only($attributes, ['email', 'password']))
            ->assertStatus(422)
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
        $user = factory(User::class)->raw();

        $attributes = array_merge($user, [
            'local_ip'              => $user['ip_address'],
            'password'              => 'password',
            'password_confirmation' => 'password',
            'g_recaptcha'           => Str::random(),
        ]);

        $this->postJson(url('/api/auth/signup'), $attributes)
            ->assertOk()
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

        $this->authenticate()->getJson(url('api/auth/user'))
            ->assertUnauthorized()
            ->assertExactJson([
                'ErrorCode' => 'LO_00',
                'ErrorDetails' => LO_00,
                'message' => LO_00
            ]);

        $this->assertFalse(
            $this->user->tokens()->where('revoked', false)->exists()
        );
    }

    /**
     * Test retrieving Authenticated User.
     *
     * @return void
     */
    public function testCurrentUserRetrieving()
    {
        $this->authenticate();
        
        $this->getJson(url('api/auth/user'))
            ->assertOk()
            ->assertJsonStructure([
                'id', 'email', 'first_name', 'middle_name', 'last_name', 'default_route', 'already_logged_in', 'role_id', 'role_name', 'privileges'
            ]);
    }
}
