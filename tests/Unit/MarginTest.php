<?php

namespace Tests\Unit;

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
        $response = $this->getJson(url('api/margins'), $this->authorizationHeader);

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
        $attributes = $this->makeGenericMarginAttributes();

        $response = $this->postJson(url('api/margins'), $attributes, $this->authorizationHeader);

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
        $attributes = $this->makeGenericMarginAttributes();

        data_set($attributes, 'value', 150);

        $response = $this->postJson(url('api/margins'), $attributes, $this->authorizationHeader);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
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

        $attributes = [
            'quote_type' => 'New',
            'method' => 'No Margin',
            'is_fixed' => false,
            'country_id' => $country->id,
            'vendor_id' => $vendor->id,
            'value' => rand(1, 99)
        ];

        $response = $this->postJson(url('api/margins'), $attributes, $this->authorizationHeader);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vendor_id']);
    }

    /**
     * Test Margin Updating.
     *
     * @return void
     */
    public function testMarginUpdating()
    {
        $attributes = $this->makeGenericMarginAttributes();

        $margin = app('margin.repository')->create($attributes);

        $newAttributes = $this->makeGenericMarginAttributes();

        $keys = array_diff(array_keys($attributes), ['user_id']);

        $response = $this->patchJson(url("api/margins/{$margin->id}"), $newAttributes, $this->authorizationHeader);

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
        $margin = app('margin.repository')->create($this->makeGenericMarginAttributes());

        $response = $this->putJson(url("api/margins/activate/{$margin->id}"), [], $this->authorizationHeader);

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
        $margin = app('margin.repository')->create($this->makeGenericMarginAttributes());

        $response = $this->putJson(url("api/margins/deactivate/{$margin->id}"), [], $this->authorizationHeader);

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
        $margin = app('margin.repository')->create($this->makeGenericMarginAttributes());

        $response = $this->deleteJson(url("api/margins/{$margin->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $margin->refresh();

        $this->assertNotNull($margin->deleted_at);
    }

    protected function makeGenericMarginAttributes(): array
    {
        $vendor = app('vendor.repository')->random();

        return [
            'quote_type' => 'New',
            'method' => 'No Margin',
            'is_fixed' => false,
            'country_id' => $vendor->countries->first()->id,
            'vendor_id' => $vendor->id,
            'value' => (float) rand(1, 99),
            'user_id' => $this->user->id
        ];
    }
}
