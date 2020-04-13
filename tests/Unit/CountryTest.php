<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Data\Country;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\WithFakeUser;
use Arr;
use Illuminate\Database\Eloquent\Builder;

class CountryTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser;

    protected static $assertableAttributes = [
        'id', 'name', 'iso_3166_2', 'currency_name', 'currency_code', 'currency_symbol', 'default_currency'
    ];

    /**
     * Test Country creating with valid attributes.
     *
     * @return void
     */
    public function testCountryCreating()
    {
        $attributes = factory(Country::class)->raw();

        $this->postJson(url('api/countries'), $attributes)
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes);
    }

    /**
     * Test updating a newly created Country.
     *
     * @return void
     */
    public function testCountryUpdating()
    {
        $country = factory(Country::class)->create();

        $attributes = factory(Country::class)->raw();

        $this->patchJson(url("api/countries/{$country->id}"), $attributes)
            ->assertOk()
            ->assertJsonStructure(static::$assertableAttributes)
            ->assertJsonFragment(Arr::only($attributes, static::$assertableAttributes));
    }

    /**
     * Test updating system defined Country. System Countries updating is forbidden.
     *
     * @return void
     */
    public function testSystemCountryUpdating()
    {
        $country = app('country.repository')->random(1, fn (Builder $query) => $query->system());

        $attributes = factory(Country::class)->raw();

        $this->patchJson(url("api/countries/{$country->id}"), $attributes)->assertForbidden();
    }

    /**
     * Test deleting a newly created Country.
     *
     * @return void
     */
    public function testCountryDeleting()
    {
        $country = factory(Country::class)->create();

        $this->deleteJson(url("api/countries/{$country->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($country);
    }

    /**
     * Test activating a newly created Country.
     *
     * @return void
     */
    public function testCountryActivating()
    {
        $country = tap(factory(Country::class)->create())->deactivate();

        $this->putJson(url("api/countries/activate/{$country->id}"))
            ->assertOk()->assertExactJson([true]);

        $this->assertNotNull($country->refresh()->activated_at);
    }

    /**
     * Test activating a newly created Country.
     *
     * @return void
     */
    public function testCountryDeactivating()
    {
        $country = factory(Country::class)->create();

        $this->putJson(url("api/countries/deactivate/{$country->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($country->refresh()->activated_at);
    }
}
