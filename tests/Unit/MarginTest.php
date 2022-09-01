<?php

namespace Tests\Unit;

use App\Models\Data\Country;
use App\Models\Quote\Margin\CountryMargin;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing,};

/**
 * @group build
 */
class MarginTest extends TestCase
{
    use AssertsListing, DatabaseTransactions;

    protected array $truncatableTables = [
        'country_margins'
    ];

    /**
     * Test Margin listing.
     *
     * @return void
     */
    public function testMarginListing()
    {
        $this->authenticateApi();

        $response = $this->getJson(url('api/margins'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_value' => 'asc',
            'order_by_country' => 'asc'
        ]);

        $this->getJson(url('api/margins?' . $query))->assertOk();
    }

    /**
     * Test Margin creating with valid attributes.
     *
     * @return void
     */
    public function testMarginCreating()
    {
        $this->authenticateApi();

        $attributes = factory(CountryMargin::class)->raw();

        $keys = array_diff(array_keys($attributes), ['user_id']);

        $this->postJson(url('api/margins'), $attributes)
            ->assertOk()
            ->assertJsonStructure(array_merge($keys, ['created_at']));
    }

    /**
     * Test Margin creating with percentage value greater than 100.
     *
     * @return void
     */
    public function testMarginCreatingWithValueGreaterThan100()
    {
        $this->authenticateApi();

        $attributes = factory(CountryMargin::class)->raw(['value' => 150]);

        $this->postJson(url('api/margins'), $attributes)
            ->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['value']]
            ]);
    }

    /**
     * Test Margin creating with Country non-related to the Vendor.
     *
     * @return void
     */
    public function testMarginCreatingWithCountryNonRelatedToVendor()
    {
        $this->authenticateApi();

        $vendor = factory(Vendor::class)->create();

        $country = Country::query()->whereNotIn('id', $vendor->countries->pluck('id'))->first();

        $attributes = factory(CountryMargin::class)->raw(['vendor_id' => $vendor->id, 'country_id' => $country->id]);

        $this->postJson(url('api/margins'), $attributes)
            ->assertStatus(422)
            ->assertJsonStructure(['Error' => ['original' => ['vendor_id']]]);
    }

    /**
     * Test Margin Updating.
     *
     * @return void
     */
    public function testMarginUpdating()
    {
        $this->authenticateApi();

        $margin = factory(CountryMargin::class)->create();

        $attributes = factory(CountryMargin::class)->raw();

        $keys = array_keys($attributes);

        $this->patchJson(url("api/margins/{$margin->id}"), $attributes)
            ->assertOk()
            ->assertJsonFragment(array_intersect_key($attributes, array_flip($keys)));
    }

    /**
     * Test Margin Activating.
     *
     * @return void
     */
    public function testMarginActivating()
    {
        $this->authenticateApi();

        $margin = tap(factory(CountryMargin::class)->create())->deactivate();

        $this->putJson(url("api/margins/activate/{$margin->id}"))
            ->assertOk()->assertExactJson([true]);

        $this->assertNotNull($margin->refresh()->activated_at);
    }

    /**
     * Test Margin Deactivating.
     *
     * @return void
     */
    public function testMarginDeactivating()
    {
        $this->authenticateApi();

        $margin = tap(factory(CountryMargin::class)->create())->activate();

        $this->putJson(url("api/margins/deactivate/{$margin->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($margin->refresh()->activated_at);
    }

    /**
     * Test Margin Deleting.
     *
     * @return void
     */
    public function testMarginDeleting()
    {
        $this->authenticateApi();

        $margin = factory(CountryMargin::class)->create();

        $this->deleteJson(url("api/margins/{$margin->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($margin);
    }
}
