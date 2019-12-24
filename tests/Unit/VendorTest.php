<?php

namespace Tests\Unit;

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
        $response = $this->getJson(url('api/vendors'), $this->authorizationHeader);

        $this->assertListing($response);
    }

    /**
     * Test Vendor creating with valid attributes.
     *
     * @return void
     */
    public function testVendorCreating()
    {
        $attributes = $this->makeGenericVendorAttributes();

        $response = $this->postJson(url('api/vendors'), $attributes, $this->authorizationHeader);

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
        $attributes = $this->makeGenericVendorAttributes();

        $vendor = app('vendor.repository')->create($attributes);

        $newAttributes = $this->makeGenericVendorAttributes();

        $response = $this->patchJson(url("api/vendors/{$vendor->id}"), $newAttributes, $this->authorizationHeader);

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
        $vendor = app('vendor.repository')->create($this->makeGenericVendorAttributes());

        $response = $this->putJson(url("api/vendors/activate/{$vendor->id}"), [], $this->authorizationHeader);

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
        $vendor = app('vendor.repository')->create($this->makeGenericVendorAttributes());

        $response = $this->putJson(url("api/vendors/deactivate/{$vendor->id}"), [], $this->authorizationHeader);

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
        $vendor = app('vendor.repository')->create($this->makeGenericVendorAttributes());

        $response = $this->deleteJson(url("api/vendors/{$vendor->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $vendor->refresh();

        $this->assertNotNull($vendor->deleted_at);
    }

    protected function makeGenericVendorAttributes(): array
    {
        return [
            'name' => $this->faker->company,
            'short_code' => Str::random(6),
            'countries' => app('country.repository')->all()->take(4)->pluck('id')->toArray(),
            'user_id' => $this->user->id
        ];
    }
}
