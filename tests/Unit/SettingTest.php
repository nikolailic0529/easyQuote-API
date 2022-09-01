<?php

namespace Tests\Unit;

use App\Facades\Setting;
use App\Models\System\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * @group build
 */
class SettingTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    protected static array $assertableAttributes = [
        'id',
        'value',
        'possible_values',
        'is_read_only',
        'label',
        'field_title',
        'field_type',
    ];

    protected static array $assertableSections = [
        'global', 'exchange_rates', 'maintenance',
    ];

    /**
     * Test Setting listing.
     *
     * @return void
     */
    public function testSettingListing()
    {
        $this->authenticateApi();

        $response = $this->getJson(url('api/settings'))->assertOk();

        $json = $response->json();

        $this->assertTrue(
            Arr::has($json, static::$assertableSections)
        );

        $this->assertTrue(
            Arr::has(head(head($json)), static::$assertableAttributes)
        );
    }

    /**
     * Test updating Bulk Settings.
     *
     * @return void
     */
    public function testSettingsUpdating()
    {
        $this->authenticateApi();

        $settings = SystemSetting::pluck('id', 'key');

        $attributes = [
            ['id' => $settings['base_currency'], 'value' => $this->faker->randomElement(['GBP', 'EUR'])],
            ['id' => $settings['password_expiry_notification'], 'value' => mt_rand(7, 30)],
            ['id' => $settings['notification_time'], 'value' => mt_rand(1, 3)],
            ['id' => $settings['failure_report_recipients'],
                'value' => User::inRandomOrder()->limit(10)->pluck('email')->all()],
            ['id' => $settings['google_recaptcha_enabled'], 'value' => $this->faker->boolean],
        ];

        $this->patchJson(url('api/settings'), $attributes)
            ->assertOk()
            ->assertExactJson([true]);
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
     * Test Base Currency Setting updating.
     *
     * @return void
     */
    public function testBaseCurrencySettingUpdating()
    {
        $setting = Setting::findByKey('base_currency');

        $value = Collection::wrap($setting->possible_values)
            ->pluck('value')
            ->reject(fn($value) => $value === $setting->value)
            ->random();

        $setting->update(compact('value'));

        $response = $this->getJson(url('api/data/currencies'));

        $this->assertEquals($value, $response->json('0.code'));
    }

    public function testFileUploadSizeSettingUpdating()
    {
        $this->authenticateApi();

        $setting = Setting::findByKey('file_upload_size');

        $value = Collection::wrap($setting->possible_values)->min('value'); // 2 MB

        $setting->update(compact('value'));

        $file = UploadedFile::fake()->create('price_list.csv', 10000);

        $this->postJson(
            url('api/quotes/file'),
            ['quote_file' => $file, 'file_type' => QFT_PL])
//            ->dump()
            ->assertUnprocessable()
            ->assertJsonStructure([
                'Error' => ['original' => ['quote_file']],
            ]);

        /**
         * Test after updating Setting value to max possible value.
         */
        $value = Collection::wrap($setting->possible_values)->max('value'); // 10 MB

        $setting->update(compact('value'));

        $this->postJson(
            url('api/quotes/file'),
            ['quote_file' => $file, 'file_type' => QFT_PL])
            ->assertSuccessful()
            ->assertJsonMissingValidationErrors('quote_file');
    }
}
