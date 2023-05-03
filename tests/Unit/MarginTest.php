<?php

namespace Tests\Unit;

use App\Domain\Country\Models\Country;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Traits\AssertsListing;

/**
 * @group build
 */
class MarginTest extends TestCase
{
    use AssertsListing;
    use DatabaseTransactions;

    protected array $truncatableTables = [
        'country_margins',
    ];

    /**
     * Test an ability to view margin listing.
     */
    public function testCanViewMarginListing(): void
    {
        $this->authenticateApi();

        $response = $this->getJson(url('api/margins'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_value' => 'asc',
            'order_by_country' => 'asc',
        ]);

        $this->getJson(url('api/margins?'.$query))->assertOk();
    }

    /**
     * Test an ability to view an existing margin.
     */
    public function testCanViewMargin(): void
    {
        $this->authenticateApi();

        $margin = factory(CountryMargin::class)->create();

        $this->getJson('api/margins/'.$margin->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'value',
                'is_fixed',
                'vendor_id',
                'vendor' => [
                    'id',
                    'name',
                ],
                'country_id',
                'country' => [
                    'id',
                    'name',
                ],
                'quote_type',
                'method',
                'user_id',
                'created_at',
                'updated_at',
                'activated_at',
            ]);
    }

    /**
     * Test an ability to create a margin with valid attributes.
     */
    public function testCanCreateMarginWithValidAttributes(): void
    {
        $this->authenticateApi();

        $attributes = factory(CountryMargin::class)->raw();

        $keys = array_diff(array_keys($attributes), ['user_id']);

        $this->postJson(url('api/margins'), $attributes)
            ->assertOk()
            ->assertJsonStructure(array_merge($keys, ['created_at']));
    }

    /**
     * Test an ability to create a margin with value greater than 100.
     */
    public function testCanNotCreateMarginWithValueGreaterThan100(): void
    {
        $this->authenticateApi();

        $attributes = factory(CountryMargin::class)->raw(['value' => 150]);

        $this->postJson(url('api/margins'), $attributes)
            ->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['value']],
            ]);
    }

    /**
     * Test an ability to create a margin with country non-related to the selected vendor.
     */
    public function testCanNotCreateMarginWithCountryNonRelatedToVendor(): void
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
     * Test an ability to update a margin.
     */
    public function testCanUpdateMargin(): void
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
     * Test an ability to mark margin as active.
     */
    public function testCanMarkMarkAsActive(): void
    {
        $this->authenticateApi();

        $margin = tap(factory(CountryMargin::class)->create())->deactivate();

        $this->putJson(url("api/margins/activate/{$margin->id}"))
            ->assertOk()->assertExactJson([true]);

        $this->assertNotNull($margin->refresh()->activated_at);
    }

    /**
     * Test an ability to mark margin as inactive.
     */
    public function testCanMarkMarginAsInactive(): void
    {
        $this->authenticateApi();

        $margin = tap(factory(CountryMargin::class)->create())->activate();

        $this->putJson(url("api/margins/deactivate/{$margin->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($margin->refresh()->activated_at);
    }

    /**
     * Test an ability to delete margin.
     */
    public function testCanDeleteMargin(): void
    {
        $this->authenticateApi();

        $margin = factory(CountryMargin::class)->create();

        $this->deleteJson(url("api/margins/{$margin->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($margin);
    }
}
