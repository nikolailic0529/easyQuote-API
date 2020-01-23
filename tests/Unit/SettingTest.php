<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Http\UploadedFile;
use Arr, Setting;
use Illuminate\Support\Collection;

class SettingTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser;

    protected static $assertableAttributes = [
        'id',
        'value',
        'possible_values',
        'is_read_only',
        'label',
        'field_title',
        'field_type'
    ];

    /**
     * Test Setting listing.
     *
     * @return void
     */
    public function testSettingListing()
    {
        $response = $this->getJson(url('api/settings'), $this->authorizationHeader);

        $response->assertOk();

        $setting = head($response->json());

        $this->assertTrue(
            Arr::has($setting, static::$assertableAttributes)
        );
    }

    /**
     * Test updating Bulk Settings.
     *
     * @return void
     */
    public function testSettingsUpdating()
    {
        $attributes = Setting::all()
            ->reject(function ($setting) {
                return (bool) $setting->is_read_only || blank($setting->possible_values);
            })
            ->map(function ($setting) {
                $value = $setting->possible_values instanceof Collection
                    ? $setting->possible_values->random()
                    : Arr::random($setting->possible_values);

                $value = data_get($value, 'value');

                return $setting->only('id') + compact('value');
            })
            ->toArray();

        $response = $this->patchJson(url('api/settings'), $attributes, $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test Base Currency Setting updating.
     *
     * @return void
     */
    public function testBaseCurrencySettingUpdating()
    {
        $setting = Setting::findByKey('base_currency');

        $value = collect($setting->possible_values)
            ->pluck('value')
            ->reject(function ($value) use ($setting) {
                return $value === $setting->value;
            })
            ->random();

        $setting->update(compact('value'));

        $response = $this->getJson(url('api/data/currencies'));

        $currency = head($response->json());

        $this->assertEquals($value, $currency['code']);
    }

    public function testFileUploadSizeSettingUpdating()
    {
        $setting = Setting::findByKey('file_upload_size');

        $value = collect($setting->possible_values)->min('value'); // 2 MB

        $setting->update(compact('value'));

        $file = UploadedFile::fake()->create('price_list.csv', 10000);

        $response = $this->postJson(
            url('api/quotes/file'),
            [
                'quote_file' => $file,
                'file_type' => 'Distributor Price List'
            ],
            $this->authorizationHeader
        );

        $response->assertJsonStructure([
            'Error' => ['original' => ['quote_file']]
        ]);

        /**
         * Test after updating Setting value to max possible value.
         */
        $value = collect($setting->possible_values)->max('value'); // 10 MB

        $setting->update(compact('value'));

        $response = $this->postJson(
            url('api/quotes/file'),
            [
                'quote_file' => $file,
                'file_type' => 'Distributor Price List'
            ],
            $this->authorizationHeader
        );

        $response->assertOk()
            ->assertJsonMissingValidationErrors('quote_file');
    }
}
