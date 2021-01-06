<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;
use App\Models\System\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Facades\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group build
 */
class SettingTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    protected static array $assertableAttributes = [
        'id',
        'value',
        'possible_values',
        'is_read_only',
        'label',
        'field_title',
        'field_type'
    ];

    protected static array $assertableSections = [
        'global', 'exchange_rates', 'maintenance'
    ];

    /**
     * Test Setting listing.
     *
     * @return void
     */
    public function testSettingListing()
    {
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
        $settings = SystemSetting::pluck('id', 'key');

        $attributes = [
            ['id' => $settings['base_currency'], 'value' => $this->faker->randomElement(['GBP', 'EUR'])],
            ['id' => $settings['password_expiry_notification'], 'value' => mt_rand(7, 30)],
            ['id' => $settings['notification_time'], 'value' => mt_rand(1, 3)],
            ['id' => $settings['failure_report_recipients'], 'value' => User::inRandomOrder()->limit(10)->pluck('email')->all()],
            ['id' => $settings['google_recaptcha_enabled'], 'value' => $this->faker->boolean],
        ];

        $this->patchJson(url('api/settings'), $attributes)
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test Public Settings Listing.
     *
     * @return void
     */
    public function testPublicSettingsListing()
    {
        $this->app->make('auth')->guard('web')->logout();

        $response = $this->getJson('api/settings/public')->assertOk();

        $ids = SystemSetting::whereIn('key', config('settings.public'))->pluck('id');

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
            ->reject(fn ($value) => $value === $setting->value)
            ->random();

        $setting->update(compact('value'));

        $response = $this->getJson(url('api/data/currencies'));

        $this->assertEquals($value, $response->json('0.code'));
    }

    public function testFileUploadSizeSettingUpdating()
    {
        $setting = Setting::findByKey('file_upload_size');

        $value = Collection::wrap($setting->possible_values)->min('value'); // 2 MB

        $setting->update(compact('value'));

        $file = UploadedFile::fake()->create('price_list.csv', 10000);

        $this->postJson(
            url('api/quotes/file'),
            ['quote_file' => $file, 'file_type' => QFT_PL],
            $this->authorizationHeader
        )
            ->assertJsonStructure([
                'Error' => ['original' => ['quote_file']]
            ]);

        /**
         * Test after updating Setting value to max possible value.
         */
        $value = Collection::wrap($setting->possible_values)->max('value'); // 10 MB

        $setting->update(compact('value'));

        $this->postJson(
            url('api/quotes/file'),
            ['quote_file' => $file, 'file_type' => QFT_PL],
            $this->authorizationHeader
        )
            ->assertSuccessful()
            ->assertJsonMissingValidationErrors('quote_file');
    }
}
