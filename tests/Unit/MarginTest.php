<?php

namespace Tests\Unit;

use App\Models\Quote\Margin\CountryMargin;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing,
    TruncatesDatabaseTables
};
use Str;

class MarginTest extends TestCase
{
    use TruncatesDatabaseTables, DatabaseTransactions, WithFakeUser, AssertsListing;

    protected $truncatableTables = [
        'country_margins'
    ];

    /**
     * Test Margin listing.
     *
     * @return void
     */
    public function testMarginListing()
    {
        $response = $this->getJson(url('api/margins'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_value' => 'asc',
            'order_by_country' => 'asc'
        ]);

        $response = $this->getJson(url('api/margins?' . $query));

        $response->assertOk();
    }

    /**
     * Test Margin creating with valid attributes.
     *
     * @return void
     */
    public function testMarginCreating()
    {
        $attributes = factory(CountryMargin::class)->raw();

        $response = $this->postJson(url('api/margins'), $attributes);

        $keys = array_diff(array_keys($attributes), ['user_id']);

        $response->assertOk()
            ->assertJsonStructure(array_merge($keys, ['created_at']));
    }

    /**
     * Test Margin creating with percentage value greater then 100.
     *
     * @return void
     */
    public function testMarginCreatingWithValueGreaterThen100()
    {
        $attributes = factory(CountryMargin::class)->raw(['value' => 150]);

        $response = $this->postJson(url('api/margins'), $attributes);

        $response->assertStatus(422)
            ->assertJsonStructure(['Error' => ['original' => ['value']]]);
    }

    /**
     * Test Margin creating with Country non-related to the Vendor.
     *
     * @return void
     */
    public function testMarginCreatingWithCountryNonRelatedToVendor()
    {
        $vendor = app('vendor.repository')->random();

        $country = app('country.repository')->all()->whereNotIn('id', $vendor->countries->pluck('id'))->first();

        $attributes = factory(CountryMargin::class)->raw(['vendor_id' => $vendor->id, 'country_id' => $country->id]);

        $response = $this->postJson(url('api/margins'), $attributes);

        $response->assertStatus(422)
            ->assertJsonStructure(['Error' => ['original' => ['vendor_id']]]);
    }

    /**
     * Test Margin Updating.
     *
     * @return void
     */
    public function testMarginUpdating()
    {
        $margin = factory(CountryMargin::class)->create();

        $newAttributes = factory(CountryMargin::class)->raw();

        $keys = array_keys($newAttributes);

        $response = $this->patchJson(url("api/margins/{$margin->id}"), $newAttributes);

        $response->assertOk()
            ->assertJsonFragment(array_intersect_key($newAttributes, array_flip($keys)));
    }

    /**
     * Test Margin Activating.
     *
     * @return void
     */
    public function testMarginActivating()
    {
        $margin = factory(CountryMargin::class)->create();

        $response = $this->putJson(url("api/margins/activate/{$margin->id}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $margin->refresh();

        $this->assertNotNull($margin->activated_at);
    }

    /**
     * Test Margin Deactivating.
     *
     * @return void
     */
    public function testMarginDeactivating()
    {
        $margin = factory(CountryMargin::class)->create();

        $response = $this->putJson(url("api/margins/deactivate/{$margin->id}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $margin->refresh();

        $this->assertNull($margin->activated_at);
    }

    /**
     * Test Margin Deleting.
     *
     * @return void
     */
    public function testMarginDeleting()
    {
        $margin = factory(CountryMargin::class)->create();

        $response = $this->deleteJson(url("api/margins/{$margin->id}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($margin);
    }
}
