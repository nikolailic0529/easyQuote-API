<?php

namespace Tests\Feature;

use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing};

/**
 * @group build
 */
class VendorTest extends TestCase
{
    use AssertsListing;
    use DatabaseTransactions;

    /**
     * Test Vendor Listing.
     *
     * @return void
     */
    public function testCanViewPaginatedVendors()
    {
        $this->authenticateApi();

        $this->getJson('api/vendors')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'short_code', 'is_system', 'user_id', 'created_at', 'updated_at', 'deleted_at', 'activated_at', 'drafted_at', 'logo', 'image',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => ['url', 'label', 'active'],
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_short_code' => 'asc',
        ]);

        $this->getJson('api/vendors?'.$query)->assertOk();
    }

    /**
     * Test an ability to view list of the vendors.
     *
     * @return void
     */
    public function testCanViewVendorsList()
    {
        $this->authenticateApi();

        $response = $this->getJson('api/vendors/list')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'short_code',
                ],
            ]);

        $this->assertNotEmpty($response->json());
        $this->assertSame('LEN', $response->json('0.short_code'));
    }

    /**
     * Test an ability to create a new vendor.
     *
     * @return void
     */
    public function testCanCreateVendor()
    {
        $this->authenticateApi();

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
        $this->authenticateApi();

        $vendor = factory(Vendor::class)->create();

        $attributes = factory(Vendor::class)->state('countries')->raw();

        $this->patchJson(url('api/vendors/'.$vendor->getKey()), $attributes)
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
        $this->authenticateApi();

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
        $this->authenticateApi();

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
        $this->authenticateApi();

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
        $this->authenticateApi();

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
