<?php

namespace Tests\Feature;

use App\Models\Data\Country;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class CountryTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    /**
     * Test an ability to create a new country.
     *
     * @return void
     */
    public function testCanCreateCountry()
    {
        $attributes = factory(Country::class)->raw();

        $this->postJson("api/countries/", $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'iso_3166_2',
                'currency_name',
                'currency_code',
                'currency_symbol',
                'default_currency',
            ]);
    }

    /**
     * Test an ability to update an existing country.
     *
     * @return void
     */
    public function testCanUpdateCountry()
    {
        $country = factory(Country::class)->create();

        $attributes = factory(Country::class)->raw();

        $this->patchJson("api/countries/".$country->getKey(), $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'iso_3166_2',
                'currency_name',
                'currency_code',
                'currency_symbol',
                'default_currency',
            ]);
    }

    /**
     * Test can not update system country.
     *
     * @return void
     */
    public function testCanNotUpdateSystemCountry()
    {
        $country = factory(Country::class)->create(['is_system' => true]);

        $attributes = factory(Country::class)->raw();

        $this->patchJson("api/countries/".$country->getKey(), $attributes)
            ->assertForbidden();
    }

    /**
     * Test can delete an existing country.
     *
     * @return void
     */
    public function testCanDeleteCountry()
    {
        $country = factory(Country::class)->create();

        $this->deleteJson("api/countries/".$country->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson("api/countries/".$country->getKey())
            ->assertNotFound();
    }

    /**
     * Test can activate an existing country
     *
     * @return void
     */
    public function testCanActivateCountry()
    {
        $country = factory(Country::class)->create();

        $country->activated_at = null;
        $country->save();

        $this->putJson("api/countries/activate/".$country->getKey())
            ->assertOk()->assertExactJson([true]);

        $this->assertNotNull($country->refresh()->activated_at);
    }

    /**
     * Test activating a newly created Country.
     *
     * @return void
     */
    public function testCanDeactivateCountry()
    {
        $country = factory(Country::class)->create();

        $country->activated_at = now();
        $country->save();

        $this->putJson("api/countries/deactivate/".$country->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($country->refresh()->activated_at);
    }
}
