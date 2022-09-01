<?php

namespace Tests\Unit\User;

use App\Http\Middleware\PerformUserActivity;
use App\Models\Data\Country;
use App\Models\Data\Timezone;
use App\Models\Template\HpeContractTemplate;
use App\Models\User;
use App\Services\User\UserActivityService;
use Faker\Generator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\{Arr, Str};
use Tests\TestCase;

/**
 * @group build
 * @group user
 */
class AuthTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

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

        $credentials = [
            'email' => $user->email, 'password' => 'password', 'local_ip' => '192.168.99.99',
            'g_recaptcha' => Str::random(),
        ];

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
        $this->authenticateApi();

        $this->withMiddleware(PerformUserActivity::class);

        /** @var \App\Services\User\UserActivityService $activityService */
        $activityService = $this->app[UserActivityService::class];

        $activityService->updateActivityTimeOfUser($this->app['auth']->user(), now()->subHour());

        $this->getJson('api/auth/user')
            ->assertUnauthorized()
            ->assertExactJson([
                'ErrorCode' => 'LO_00',
                'ErrorDetails' => LO_00,
                'message' => LO_00,
            ]);

        $this->assertFalse(
            $this->app['auth']->user()->tokens()->where('revoked', false)->exists()
        );
    }

    /**
     * Test retrieving Authenticated User.
     *
     * @return void
     */
    public function testCanViewCurrentUser()
    {
        $this->authenticateApi();

        $this->getJson('api/auth/user')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'email',
                'first_name',
                'middle_name',
                'last_name',
                'default_route',
                'already_logged_in',
                'role_id',
                'role_name',
                'privileges',
                'companies' => [
                    '*' => ['id', 'name']
                ]
            ]);
    }

    public function testCanDeleteCurrentUserPicture(): void
    {
        $this->authenticateApi();

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
     *
     * @dataProvider currentUserDataProvider
     */
    public function testCanUpdateCurrentUser(\Closure $getData): void
    {
        $data = $getData();

        $this->authenticateApi();

        $response = $this->postJson('api/auth/user', $data)
//            ->dump()
            ->assertOk();

        $response->assertOk();

        $response = $this->getJson('api/auth/user')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'first_name',
                'last_name',
                'sales_units' => [
                    '*' => ['id', 'unit_name'],
                ],
            ]);

        foreach (Arr::except($data,
            ['password', 'current_password', 'change_password', 'delete_picture']) as $attr => $value) {
            $this->assertSame($value, $response->json($attr));
        }
    }

    protected function currentUserDataProvider(): \Generator
    {
        yield 'update first_name & last_name' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName(),
                ];
            },
        ];

        yield 'update middle_name' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'middle_name' => $faker->firstName(),
                ];
            },
        ];

        yield 'update phone' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'phone' => $faker->e164PhoneNumber(),
                ];
            },
        ];

        yield 'update timezone_id' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'timezone_id' => Timezone::query()->get()->random()->getKey(),
                ];
            },
        ];

        yield 'update country_id' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'country_id' => Country::query()->get()->random()->getKey(),
                ];
            },
        ];

        yield 'update hpe_contract_template_id' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'hpe_contract_template_id' => factory(HpeContractTemplate::class)->create()->getKey(),
                ];
            },
        ];

        yield 'set null hpe_contract_template_id' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'hpe_contract_template_id' => null,
                ];
            },
        ];

        yield 'delete picture' => [
            function () {
                return [
                    'delete_picture' => true,
                ];
            },
        ];

        yield 'change password' => [
            function () {
                $faker = app(Generator::class);

                return [
                    'change_password' => true,
                    'current_password' => 'password',
                    'password' => $faker->password(),
                ];
            },
        ];
    }
}
