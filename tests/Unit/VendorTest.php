<?php

namespace Tests\Unit;

use App\Models\Vendor;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};
use Str, Arr;

class VendorTest extends TestCase
{
    use WithFakeUser, AssertsListing;

    /**
     * Test Vendor Listing.
     *
     * @return void
     */
    public function testVendorListing()
    {
        $response = $this->getJson(url('api/vendors'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_short_code' => 'asc'
        ]);

        $this->getJson(url('api/vendors?' . $query))->assertOk();
    }

    /**
     * Test Vendor creating with valid attributes.
     *
     * @return void
     */
    public function testVendorCreating()
    {
        $attributes = factory(Vendor::class)->state('countries')->raw();

        $this->postJson(url('api/vendors'), $attributes)
            ->assertOk()
            ->assertJsonStructure(
                array_keys(Arr::except($attributes, ['user_id', 'countries']))
            );
    }

    /**
     * Test updating a newly created Vendor.
     *
     * @return void
     */
    public function testVendorUpdating()
    {
        $vendor = factory(Vendor::class)->create();

        $attributes = factory(Vendor::class)->state('countries')->raw();

        $this->patchJson(url("api/vendors/{$vendor->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure(
                array_keys(Arr::except($attributes, ['user_id', 'countries']))
            )
            ->assertJsonFragment(
                Arr::except($attributes, ['user_id', 'countries'])
            );
    }

    /**
     * Test activating a newly created Vendor.
     *
     * @return void
     */
    public function testVendorActivating()
    {
        $vendor = tap(factory(Vendor::class)->create())->deactivate();

        $this->putJson(url("api/vendors/activate/{$vendor->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNotNull($vendor->refresh()->activated_at);
    }

    /**
     * Test deactivating a newly created Vendor.
     *
     * @return void
     */
    public function testVendorDeactivating()
    {
        $vendor = tap(factory(Vendor::class)->create())->activate();

        $this->putJson(url("api/vendors/deactivate/{$vendor->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($vendor->refresh()->activated_at);
    }

    /**
     * Test deleting a newly created Vendor.
     *
     * @return void
     */
    public function testVendorDeleting()
    {
        $vendor = factory(Vendor::class)->create();

        $this->deleteJson(url("api/vendors/{$vendor->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($vendor);
    }

    /**
     * Test showing a newly created Vendor.
     *
     * @return void
     */
    public function testVendorShowing()
    {
        $attributes = factory(Vendor::class)->state('countries')->raw();

        /** @var Vendor */
        $vendor = app('vendor.repository')->create($attributes);

        $this->assertTrue($vendor->countries->isNotEmpty());

        $this->getJson('api/vendors/'.$vendor->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'user_id', 'logo', 'created_at', 'short_code',
                'countries' => [
                    ['id', 'iso_3166_2', 'name', 'flag']
                ]
            ]);
    }
}
