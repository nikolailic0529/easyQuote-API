<?php

namespace Tests\Feature;

use App\Domain\Country\Models\Country;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Settings\Models\SystemSetting;
use App\Domain\Timezone\Models\Timezone;
use App\Domain\User\Middleware\PerformUserActivity;
use App\Domain\User\Models\User;
use App\Domain\User\Services\UserActivityService;
use Faker\Generator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 * @group user
 */
class AuthTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::query()
            ->where('key', 'google_recaptcha_enabled')
            ->update(['value' => false]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    /**
     * Test an ability to authenticate with valid credentials.
     */
    public function testCanAuthenticateWithValidCredentials(): void
    {
        $user = User::factory()->create();

        $attributes = [
            'email' => $user->email,
            'local_ip' => $user->ip_address,
            'password' => 'password',
            'g_recaptcha' => Str::random(),
        ];

        $response = $this->postJson('/api/auth/signin', $attributes);

        $response
//            ->dump()
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at']);
    }

    /**
     * Test an ability to authenticate with invalid credentials.
     */
    public function testCanNotAuthenticateWithInvalidCredentials(): void
    {
        $user = User::factory()->create();

        $attributes = [
            'email' => $user->email,
            'local_ip' => $user->ip_address,
            'password' => Str::random(20),
            'g_recaptcha' => Str::random(),
        ];

        $this->postJson('/api/auth/signin', $attributes)
            ->assertForbidden();
    }

    /**
     * Test an ability to authenticate with valid credentials of blocked user.
     */
    public function testCanNotAuthenticateWithValidCredentialsOfBlockedUser(): void
    {
        $user = tap(User::factory()->create())->deactivate();

        $credentials = [
            'email' => $user->email, 'password' => 'password', 'local_ip' => '192.168.99.99',
            'g_recaptcha' => Str::random(),
        ];

        $response = $this->postJson('/api/auth/signin', $credentials)
            ->assertUnprocessable();

        $this->assertTrue(Str::contains($response->json('message'), 'user is blocked'));
    }

    /**
     * Test user is being logged out due to inactivity.
     */
    public function testUserIsBeingLoggedOutDueToInactivity(): void
    {
        $this->authenticateApi();

        $this->withMiddleware(PerformUserActivity::class);

        /** @var \App\Domain\User\Services\UserActivityService $activityService */
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
     * Test an ability to view current authenticated user.
     */
    public function testCanViewCurrentUser(): void
    {
        $this->authenticateApi();

        $r = $this->getJson('api/auth/user')
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
                    '*' => ['id', 'name'],
                ],
                'sales_units' => [
                    '*' => [
                        'id',
                        'unit_name',
                    ],
                ],
                'notification_settings' => [
                    '*' => [
                        'label',
                        'key',
                        'controls' => [
                            '*' => [
                                'label',
                                'key',
                                'email_notif',
                                'app_notif',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertSame('Activities', $r->json('notification_settings.0.label'));

        $r = $this->getJson('api/auth/user', headers: ['X-User-Lang' => 'fr'])
            ->assertOk();

        $this->assertSame('Activité', $r->json('notification_settings.0.label'));

        $r = $this->getJson('api/auth/user', headers: ['X-User-Lang' => 'de'])
            ->assertOk();

        $this->assertSame('Aktivitäten', $r->json('notification_settings.0.label'));
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

        $assertableData = Arr::except($data, ['password', 'current_password', 'change_password', 'delete_picture']);

        foreach (Arr::dot($assertableData) as $attr => $value) {
            $this->assertSame($value, $response->json($attr));
        }
    }

    /**
     * Test an ability to update notification settings of the current user.
     */
    public function testCanUpdateNotificationSettingsOfCurrentUser(): void
    {
        $this->authenticateApi();

        $data = [
            [
                'key' => 'activities',
                'controls' => [
                    [
                        'key' => 'is_active',
                        'email_notif' => false,
                        'app_notif' => false,
                    ],
                ],
            ],
        ];

        $this->postJson('api/auth/user', ['notification_settings' => $data])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/auth/user')
            ->assertOk()
            ->assertJsonStructure([
                'notification_settings' => [
                    '*' => [
                        'label',
                        'key',
                        'controls' => [
                            '*' => [
                                'email_notif',
                                'app_notif',
                            ],
                        ],
                    ],
                ],
            ]);

        $settingsMap = collect($response->json('notification_settings'))
            ->lazy()
            ->keyBy('key')
            ->map(static function (array $group): array {
                return collect($group['controls'])
                    ->lazy()
                    ->keyBy('key')
                    ->map(static function (array $control): array {
                        return Arr::only($control, ['email_notif', 'app_notif']);
                    })
                    ->all();
            })
            ->all();

        $this->assertFalse(Arr::get($settingsMap, 'activities.is_active.email_notif'));
        $this->assertFalse(Arr::get($settingsMap, 'activities.is_active.app_notif'));
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

//        yield 'notification settings' => [
//            function () {
//                $faker = app(Generator::class);
//
//                return [
//                    'notification_settings' => [
//                        'activities' => [
//                            'is_active' => $faker->boolean(),
//                        ],
//                        'emails' => [
//                            'is_active' => $faker->boolean(),
//                        ],
//                        'quotes' => [
//                            'is_active' => $faker->boolean(),
//                            'permissions_change' => $faker->boolean(),
//                            'ownership_change' => $faker->boolean(),
//                            'status_change' => $faker->boolean(),
//                            'expiration' => $faker->boolean(),
//                        ],
//                        'accounts_and_contacts' => [
//                            'is_active' => $faker->boolean(),
//                            'ownership_change' => $faker->boolean(),
//                        ],
//                        'opportunities' => [
//                            'is_active' => $faker->boolean(),
//                            'move' => $faker->boolean(),
//                            'ownership_change' => $faker->boolean(),
//                            'status_change' => $faker->boolean(),
//                        ],
//                        'sync' => [
//                            'is_active' => $faker->boolean(),
//                        ],
//                    ],
//                ];
//            },
//        ];
    }
}
