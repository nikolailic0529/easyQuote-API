<?php

namespace Tests\Unit;

use App\DTO\WorldwideQuote\AssetServiceLookupData;
use App\DTO\WorldwideQuote\AssetServiceLookupDataCollection;
use App\DTO\WorldwideQuote\AssetServiceLookupResult;
use App\Services\WorldwideQuote\AssetServiceLookupService;
use Tests\TestCase;

class AssetWarrantyLookupTest extends TestCase
{
    /**
     * Test an ability to process batch warranty lookup.
     *
     * @return void
     * @throws \App\Services\Exceptions\ValidationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testProcessesBatchWarrantyLookup()
    {
        $service = $this->app->make(AssetServiceLookupService::class);

        $lookupData = [
            new AssetServiceLookupData([
                'asset_id' => '6ca37d8c-2033-4d0e-a9e8-dbfefd4e31bc',
                'vendor_short_code' => 'LEN',
                'serial_no' => '06HNBAB',
                'country_code' => 'GB',
                'sku' => '5462K5G'
            ]),
            new AssetServiceLookupData([
                'asset_id' => '6019daa6-a8b0-47ac-a5a0-dcdbdb136fbc',
                'vendor_short_code' => 'LEN',
                'serial_no' => '06HNBAB',
                'country_code' => 'GB',
                'sku' => '5462K5G'
            ]),
            new AssetServiceLookupData([
                'asset_id' => '9ef1bea8-32ec-4f60-931f-4bebd20fe0d2',
                'vendor_short_code' => 'LEN',
                'serial_no' => '06HNBAB',
                'country_code' => 'GB',
                'sku' => '5462K5G'
            ]),
            new AssetServiceLookupData([
                'asset_id' => '03394b0e-1534-4205-9ce5-fdc8e6a731d0',
                'vendor_short_code' => 'LEN',
                'serial_no' => '06HNBAB',
                'country_code' => 'GB',
                'sku' => '5462K5G'
            ])
        ];

        $lookupCollection = new AssetServiceLookupDataCollection($lookupData);

        $result = $service->performBatchWarrantyLookup($lookupCollection);

        foreach ($result as $assetResult) {
            $this->assertInstanceOf(AssetServiceLookupResult::class, $assetResult);
        }
    }
}
