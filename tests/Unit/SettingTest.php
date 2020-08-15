<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Arr, Setting;

class SettingTest extends TestCase
{
    use WithFakeUser;

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
        $attributes = Setting::all()
            ->reject(fn ($setting) => $setting->is_read_only || blank($setting->possible_values))
            ->map(function ($setting) {
                $value = data_get(
                    Collection::wrap($setting->possible_values)->random(),
                    'value'
                );

                return $setting->only('id') + compact('value');
            })
            ->toArray();

        $this->patchJson(url('api/settings'), $attributes)
            ->assertOk()
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
            ->assertOk()
            ->assertJsonMissingValidationErrors('quote_file');
    }
}
