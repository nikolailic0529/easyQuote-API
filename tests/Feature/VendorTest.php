<?php

namespace Tests\Feature;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Arr, Str};
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing, WithFakeUser};

/**
 * @group build
 */
class VendorTest extends TestCase
{
    use WithFakeUser, AssertsListing, DatabaseTransactions;

    /**
     * Test Vendor Listing.
     *
     * @return void
     */
    public function testCanViewPaginatedVendors()
    {
        $response = $this->getJson(url('api/vendors'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_short_code' => 'asc',
        ]);

        $this->getJson(url('api/vendors?'.$query))->assertOk();
    }

    /**
     * Test an ability to view list of the vendors.
     *
     * @return void
     */
    public function testCanViewVendorsList()
    {
        $this->getJson('api/vendors/list')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'short_code'
                ]
            ]);
    }

    /**
     * Test an ability to create a new vendor.
     *
     * @return void
     */
    public function testCanCreateVendor()
    {
        $attributes = factory(Vendor::class)->state('countries')->raw();

        $this->postJson(url('api/vendors'), $attributes)
            ->assertOk()
            ->assertJsonStructure(
                array_keys(Arr::except($attributes, ['user_id', 'countries']))
            );
    }

    /**
     * Test an ability to update an existing vendor.
     *
     * @return void
     */
    public function testCanUpdateVendor()
    {
        $vendor = factory(Vendor::class)->create();

        $attributes = factory(Vendor::class)->state('countries')->raw();

        $this->patchJson(url("api/vendors/".$vendor->getKey()), $attributes)
            ->assertOk()
            ->assertJsonStructure(
                array_keys(Arr::except($attributes, ['user_id', 'countries']))
            )
            ->assertJsonFragment(
                Arr::except($attributes, ['user_id', 'countries'])
            );
    }

    /**
     * Test an ability to activate an existing vendor.
     *
     * @return void
     */
    public function testCanActivateVendor()
    {
        $vendor = tap(factory(Vendor::class)->create())->deactivate();

        $this->putJson(url("api/vendors/activate/{$vendor->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/vendors/'.$vendor->getKey())->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNotNull($response->json('activated_at'));
    }

    /**
     * Test an ability to deactivate an existing vendor.
     *
     * @return void
     */
    public function testCanDeactivateVendor()
    {
        $vendor = tap(factory(Vendor::class)->create())->activate();

        $this->putJson(url("api/vendors/deactivate/{$vendor->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/vendors/'.$vendor->getKey())->assertOk()
            ->assertJsonStructure([
                'id', 'activated_at',
            ]);

        $this->assertNull($response->json('activated_at'));
    }

    /**
     * Test an ability to delete an existing vendor.
     *
     * @return void
     */
    public function testCanDeleteVendor()
    {
        $vendor = factory(Vendor::class)->create();

        $this->deleteJson(url("api/vendors/{$vendor->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/vendors/'.$vendor->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to view an existing vendor.
     *
     * @return void
     */
    public function testCanViewVendor()
    {
        $vendor = factory(Vendor::class)->create();

        $this->getJson('api/vendors/'.$vendor->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'user_id', 'logo', 'created_at', 'short_code',
                'countries' => [
                    '*' => ['id', 'iso_3166_2', 'name', 'flag'],
                ],
            ]);
    }
}
