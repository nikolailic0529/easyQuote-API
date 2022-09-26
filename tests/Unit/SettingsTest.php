<?php

namespace Tests\Unit;

use App\Models\System\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group build
 */
class SettingsTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test Setting listing.
     */
    public function testCanViewSettings(): void
    {
        $this->authenticateApi();

        $r = $this->getJson('api/settings')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'global' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
                'mail' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
                'exchange_rates' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
                'maintenance' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
            ]);

        foreach ($r->json() as $settings) {
            foreach ($settings as $property) {
                $this->assertNotEmpty($property['field_type']);
            }
        }
    }

    /**
     * Test an ability to view exposed settings.
     */
    public function testCanViewExposedSettings(): void
    {
        $this->app->make('auth')->guard('web')->logout();

        $response = $this
            ->getJson('api/settings/public')
//            ->dump()
            ->assertJsonStructure([
                '*' => [
                    '*' => ['id', 'value', 'label', 'field_title', 'field_type'],
                ],
            ])
            ->assertOk();

        $ids = SystemSetting::query()->whereIn('key', config('settings.public'))->get()->modelKeys();

        $responseIds = $response->json('*.*.id');

        $this->assertSameSize($ids, $responseIds);

        foreach ($responseIds as $id) {
            $this->assertContains($id, $ids);
        }
    }

    /**
     * Test an ability to update settings.
     *
     * @dataProvider settingPropertyPayloadProvider
     */
    public function testCanUpdateSettings(string $property, mixed $data)
    {
        $this->authenticateApi();

        [$section, $property] = explode('.', $property);

        /** @var SystemSetting $settingsProperty */
        $settingsProperty = SystemSetting::where('key', $property)->sole();

        if (is_callable($data)) {
            $data = $data();
        }

        $payload = [
            ['id' => $settingsProperty->getKey(), 'value' => $data],
        ];

        $r = $this->patchJson('api/settings', $payload);

        if (!$r->isOk()) {
            $r->dump();
        }

        $r->assertOk();

        $r = $this->getJson('api/settings')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'global' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
                'mail' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
                'exchange_rates' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
                'maintenance' => [
                    '*' => [
                        'id',
                        'value',
                        'possible_values',
                        'is_read_only',
                        'label',
                        'field_title',
                        'field_type',
                    ],
                ],
            ]);

        $this->assertContains($property, $r->json("$section.*.key"));

        $propertyResponse = collect($r->json($section))->first(static function (array $item) use ($settingsProperty
        ): bool {
            return $item['id'] === $settingsProperty->getKey();
        });

        $this->assertNotNull($propertyResponse);

        $this->assertSame($data, $propertyResponse['value']);
    }

    protected function settingPropertyPayloadProvider(): \Generator
    {
        foreach (['GBP', 'EUR', 'USD'] as $item) {
            yield "base currency: $item" => [
                "global.base_currency",
                $item,
            ];
        }

        foreach (range(7, 30) as $item) {
            yield "password expiry notification: $item" => [
                "global.password_expiry_notification",
                $item,
            ];
        }

        foreach (range(1, 3) as $item) {
            yield "notification time: $item" => [
                "global.notification_time",
                $item,
            ];
        }

        yield "failure report recipients" => [
            "global.failure_report_recipients",
            static function (): array {
                return User::factory()->count(10)->create()->modelKeys();
            },
        ];
        foreach (range(1, 12) as $case) {
            yield "pipeliner sync schedule: $case" => [
                "global.pipeliner_sync_schedule",
                $case,
            ];
        }

        foreach ([true, false] as $item) {
            yield "google recaptcha enabled: $item" => [
                "global.google_recaptcha_enabled",
                $item,
            ];
        }

        foreach (range(1000, 5000, step: 1000) as $item) {
            yield "mail limit: $item" => [
                "mail.mail_limit",
                $item,
            ];
        }
    }
}
