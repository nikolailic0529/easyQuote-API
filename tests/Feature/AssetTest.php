<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class AssetTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    /**
     * Test can view assets listing.
     *
     * @return void
     */
    public function testCanViewAssetsListing()
    {
        factory(Asset::class, 20)->create();

        $this->getJson('api/assets')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'asset_category_id',
                        'user_id',
                        'address_id',
                        'vendor_id',
                        'quote_id',
                        'rfq_number',
                        'vendor_short_code',
                        'asset_category_name',
                        'unit_price',
                        'base_warranty_start_date',
                        'base_warranty_end_date',
                        'active_warranty_start_date',
                        'active_warranty_end_date',
                        'product_number',
                        'serial_number',
                        'product_description',
                        'product_image',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total'
                ],
            ]);
    }

    /**
     * Test can create a new asset.
     *
     * @return void
     */
    public function testCanCreateNewAsset()
    {
        $attributes = factory(Asset::class)->raw();

        $response = $this->postJson('api/assets', $attributes)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'asset_category_id',
                'user_id',
                'address_id',
                'vendor_id',
                'quote_id',
                'rfq_number',
                'vendor_short_code',
                'asset_category_name',
                'unit_price',
                'base_warranty_end_date',
                'base_warranty_start_date',
                'active_warranty_end_date',
                'active_warranty_start_date',
                'product_number',
                'serial_number',
                'product_description',
                'product_image',
                'created_at',
                'updated_at'
            ]);

        $modelKey = $response->json('id');

        $this->getJson("api/assets/$modelKey")->assertOk();
    }

    /**
     * Test can update a newly created asset.
     *
     * @return void
     */
    public function testCanUpdateAsset()
    {
        /** @var Asset */
        $asset = factory(Asset::class)->create();

        $attributes = factory(Asset::class)->raw();

        $response = $this->patchJson('api/assets/'.$asset->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'asset_category_id',
                'user_id',
                'address_id',
                'vendor_id',
                'quote_id',
                'rfq_number',
                'vendor_short_code',
                'asset_category_name',
                'unit_price',
                'base_warranty_end_date',
                'base_warranty_start_date',
                'active_warranty_end_date',
                'active_warranty_start_date',
                'product_number',
                'serial_number',
                'product_description',
                'product_image',
                'created_at',
                'updated_at'
            ]);

        $modelKey = $response->json('id');

        $this->getJson("api/assets/$modelKey")->assertOk();
    }

    /**
     * Test can delete a newly created asset.
     *
     * @return void
     */
    public function testCanDeleteAsset()
    {
        /** @var Asset */
        $asset = factory(Asset::class)->create();

        $this->deleteJson('api/assets/'.$asset->getKey())->assertOk();

        $this->getJson("api/assets/{$asset->getKey()}")->assertNotFound();
    }
}
