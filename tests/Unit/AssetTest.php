<?php

namespace Tests\Unit;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Unit\Traits\AssertsListing;
use Tests\Unit\Traits\TruncatesDatabaseTables;
use Tests\Unit\Traits\WithFakeUser;

class AssetTest extends TestCase
{
    use WithFakeUser, AssertsListing, TruncatesDatabaseTables;

    protected array $truncatableTables = [
        'assets'
    ];

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testAssetsListing()
    {
        $response = $this->getJson('api/assets')->assertOk();

        $this->assertListing($response);
    }

    /**
     * Test creating a new asset.
     *
     * @return void
     */
    public function testAssetCreating()
    {
        $attributes = factory(Asset::class)->raw();

        $response = $this->postJson('api/assets', $attributes)
            ->assertCreated();

        $this->assertDatabaseHas('assets', ['id' => $response->json('id'), 'deleted_at' => null]);
    }

    /**
     * Test updating a newly created asset.
     *
     * @return void
     */
    public function testAssetUpdating()
    {
        /** @var Asset */
        $asset = factory(Asset::class)->create();

        $attributes = factory(Asset::class)->raw();

        $this->patchJson('api/assets/' . $asset->getKey(), $attributes)->assertOk();

        $asset->refresh();

        $this->assertModelAttributes($asset, $attributes);
    }

    /**
     * Test deleting a newly created asset.
     *
     * @return void
     */
    public function testAssetDeleting()
    {
        /** @var Asset */
        $asset = factory(Asset::class)->create();

        $this->deleteJson('api/assets/' . $asset->getKey())->assertOk();

        $this->assertSoftDeleted($asset);
    }
}
