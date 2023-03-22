<?php

namespace Tests\Feature;

use App\Domain\Country\Models\Country;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class CountryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to create a new country.
     */
    public function testCanCreateCountry(): void
    {
        $this->authenticateApi();

        $attributes = factory(Country::class)->raw(['name' => Str::random(100)]);

        $this->postJson('api/countries/', $attributes)
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
     */
    public function testCanUpdateCountry(): void
    {
        $this->authenticateApi();

        $country = factory(Country::class)->create();

        $attributes = factory(Country::class)->raw(['name' => Str::random(100)]);

        $this->patchJson('api/countries/'.$country->getKey(), $attributes)
//            ->dump()
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
     */
    public function testCanNotUpdateSystemCountry(): void
    {
        $this->authenticateApi();

        $country = factory(Country::class)->create(['is_system' => true]);

        $attributes = factory(Country::class)->raw();

        $this->patchJson('api/countries/'.$country->getKey(), $attributes)
            ->assertForbidden();
    }

    /**
     * Test can delete an existing country.
     */
    public function testCanDeleteCountry(): void
    {
        $this->authenticateApi();

        $country = factory(Country::class)->create();

        $this->deleteJson('api/countries/'.$country->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/countries/'.$country->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to mark an existing country as active.
     */
    public function testCanMarkCountryAsActive(): void
    {
        $this->authenticateApi();

        $country = factory(Country::class)->create();

        $country->activated_at = null;
        $country->save();

        $this->putJson('api/countries/activate/'.$country->getKey())
            ->assertOk()->assertExactJson([true]);

        $this->assertNotNull($country->refresh()->activated_at);
    }

    /**
     * Test an ability to mark an existing country as inactive.
     */
    public function testCanMarkCountryAsInactive(): void
    {
        $this->authenticateApi();

        $country = factory(Country::class)->create();

        $country->activated_at = now();
        $country->save();

        $this->putJson('api/countries/deactivate/'.$country->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($country->refresh()->activated_at);
    }
}
