<?php

namespace Tests\Unit\User;

use App\Models\SalesUnit;
use App\Models\User;
use App\Services\User\UserActivityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\{Arr, Str};
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class AuthTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    protected bool $dontAuthenticate = true;

    protected static array $assertableAttributes = ['email', 'password', 'local_ip'];

    /**
     * Test Authentication of the existing user.
     *
     * @return void
     */
    public function testAuthExistingUser()
    {
        $user = User::factory()->create();

        $attributes = [
            'email' => $user->email,
            'local_ip' => $user->ip_address,
            'password' => 'password',
            'g_recaptcha' => Str::random(),
        ];

        $response = $this->postJson(url('/api/auth/signin'), $attributes);

        $response
//            ->dump()
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }

    /**
     * Test Failed Authentication of the existing user.
     *
     * @return void
     */
    public function testFailingAuthExistingUser()
    {
        $user = User::factory()->create();

        $attributes = [
            'email' => $user->email,
            'local_ip' => $user->ip_address,
            'password' => Str::random(20),
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
        $user = tap(User::factory()->create())->deactivate();

        $credentials = ['email' => $user->email, 'password' => 'password', 'local_ip' => '192.168.99.99',
            'g_recaptcha' => Str::random()];

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
        $this->markTestSkipped('Skipped since IP detection has been disabled');

        $user = User::factory()->create();

        $attributes = [
            'email' => $user->email,
            'local_ip' => $user->ip_address,
            'password' => 'password',
            'g_recaptcha' => Str::random(),
        ];

        $this->postJson(url('/api/auth/signin'), Arr::only($attributes, ['email', 'password']))
            ->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['local_ip']],
            ]);
    }

    /**
     * Test User logout due inactivity.
     *
     * @return void
     */
    public function testLogoutDueInactivity()
    {
        /** @var \App\Services\User\UserActivityService $activityService */
        $activityService = $this->app[UserActivityService::class];

        $activityService->updateActivityTimeOfUser($this->user, now()->subHour());

        $this->authenticate()->getJson(url('api/auth/user'))
            ->assertUnauthorized()
            ->assertExactJson([
                'ErrorCode' => 'LO_00',
                'ErrorDetails' => LO_00,
                'message' => LO_00,
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
                'id', 'email', 'first_name', 'middle_name', 'last_name', 'default_route', 'already_logged_in',
                'role_id', 'role_name', 'privileges',
            ]);
    }

    public function testCanDeleteCurrentUserPicture(): void
    {
        $this->authenticate();

        $response = $this->getJson('api/auth/user')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'picture',
            ]);

        $this->assertEmpty($response->json('picture'));

        $this->postJson('api/auth/user', ['picture' => UploadedFile::fake()->image('picture.jpg')])
            ->assertOk();

        $response = $this->getJson('api/auth/user')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'picture',
            ]);

        $this->assertNotEmpty($response->json('picture'));

        $this->postJson('api/auth/user', ['delete_picture' => true])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'email', 'first_name', 'middle_name', 'last_name', 'default_route', 'already_logged_in',
                'role_id', 'role_name', 'privileges',
            ]);

        $response = $this->getJson('api/auth/user')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'picture',
            ]);

        $this->assertEmpty($response->json('picture'));
    }

    /**
     * Test an ability to update current user.
     */
    public function testCanUpdateCurrentUser(): void
    {
        $this->authenticate();

        $response = $this->getJson('api/auth/user')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'sales_units' => [
                    '*' => ['id', 'unit_name'],
                ],
            ]);

        $this->postJson('api/auth/user', $data = [
            'sales_units' => SalesUnit::query()->get()->random(2)->map(static fn(SalesUnit $unit) => ['id' => $unit->getKey()])->all(),
        ])
            ->assertOk();

        $response = $this->getJson('api/auth/user')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'sales_units' => [
                    '*' => ['id', 'unit_name'],
                ],
            ]);

        foreach ($data['sales_units'] as $unit) {
            $this->assertContains($unit['id'], $response->json('sales_units.*.id'));
        }
    }
}
