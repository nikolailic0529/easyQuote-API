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
    use DatabaseTransactions, WithFakeUser, AssertsListing;

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

        $response = $this->getJson(url('api/vendors?' . $query));

        $response->assertOk();
    }

    /**
     * Test Vendor creating with valid attributes.
     *
     * @return void
     */
    public function testVendorCreating()
    {
        $attributes = factory(Vendor::class)->state('countries')->raw();

        $response = $this->postJson(url('api/vendors'), $attributes);

        $response->assertOk()
            ->assertJsonStructure(array_keys(Arr::except($attributes, ['user_id', 'countries'])));
    }

    /**
     * Test updating a newly created Vendor.
     *
     * @return void
     */
    public function testVendorUpdating()
    {
        $vendor = factory(Vendor::class)->create();

        $newAttributes = factory(Vendor::class)->state('countries')->raw();

        $response = $this->patchJson(url("api/vendors/{$vendor->id}"), $newAttributes);

        $response->assertOk()
            ->assertJsonStructure(array_keys(Arr::except($newAttributes, ['user_id', 'countries'])))
            ->assertJsonFragment(Arr::except($newAttributes, ['user_id', 'countries']));
    }

    /**
     * Test activating a newly created Vendor.
     *
     * @return void
     */
    public function testVendorActivating()
    {
        $vendor = tap(factory(Vendor::class)->create())->deactivate();

        $response = $this->putJson(url("api/vendors/activate/{$vendor->id}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $vendor->refresh();

        $this->assertNotNull($vendor->activated_at);
    }

    /**
     * Test deactivating a newly created Vendor.
     *
     * @return void
     */
    public function testVendorDeactivating()
    {
        $vendor = tap(factory(Vendor::class)->create())->activate();

        $response = $this->putJson(url("api/vendors/deactivate/{$vendor->id}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $vendor->refresh();

        $this->assertNull($vendor->activated_at);
    }

    /**
     * Test deleting a newly created Vendor.
     *
     * @return void
     */
    public function testVendorDeleting()
    {
        $vendor = factory(Vendor::class)->create();

        $response = $this->deleteJson(url("api/vendors/{$vendor->id}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $vendor->refresh();

        $this->assertNotNull($vendor->deleted_at);
    }
}
