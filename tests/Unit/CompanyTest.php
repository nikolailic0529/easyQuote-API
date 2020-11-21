<?php

namespace Tests\Unit;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};
use Illuminate\Support\{Arr, Str};

class CompanyTest extends TestCase
{
    use WithFakeUser, AssertsListing;

    /**
     * Test Company listing.
     *
     * @return void
     */
    public function testCompanyListing()
    {
        $response = $this->getJson(url('api/companies'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_vat' => 'asc',
            'order_by_phone' => 'asc',
            'order_by_website' => 'asc',
            'order_by_type' => 'asc',
            'order_by_category' => 'asc',
        ]);

        $this->getJson(url('api/companies?' . $query))->assertOk();
    }

    /**
     * Test Company creating with valid attributes.
     *
     * @return void
     */
    public function testCompanyCreating()
    {
        $attributes = factory(Company::class)->raw();

        $this->postJson(url('api/companies'), $attributes)
            ->assertOk()
            ->assertJsonStructure(array_keys($attributes));
    }

    /**
     * Test Company creating with already existing company vat.
     *
     * @return void
     */
    public function testCompanyCreatingWithExistingVat()
    {
        $company = tap(
            factory(Company::class)->make(['user_id' => $this->user->getKey()]),
            function (Company $company) {
                unset($company['vendors']);
                $company->save();
            }
        );

        $attributes = factory(Company::class)->raw(['vat' => $company->vat]);

        $this->postJson(url('api/companies'), $attributes)
            ->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['vat']]
            ]);
    }

    /**
     * Test a newly created Company updating.
     *
     * @return void
     */
    public function testCompanyUpdating()
    {
        $attributes = factory(Company::class)->raw(['user_id' => $this->user->id]);

        $company = app('company.repository')->create($attributes);

        $newAttributes = factory(Company::class)->raw(['_method' => 'PATCH']);

        $this->postJson(url("api/companies/{$company->id}"), $newAttributes)
            ->assertOk()
            ->assertJsonStructure(array_keys($attributes))
            ->assertJsonFragment(Arr::except($newAttributes, ['_method', 'vendors']));
    }

    /**
     * Test activating a newly created Company and ensure that company appears on Quote Import screen.
     *
     * @return void
     */
    public function testCompanyActivating()
    {
        $attributes = factory(Company::class)->raw(['user_id' => $this->user->id]);

        $company = tap(app('company.repository')->create($attributes))->deactivate();

        $this->putJson(url("api/companies/activate/{$company->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNotNull($company->refresh()->activated_at);

        $this->getJson(url('api/quotes/step/1'))
            ->assertOk()
            ->assertJsonFragment(['id' => $company->id]);
    }

    /**
     * Test deactivating a newly created Company and ensure that company doesn't appear on Quote Import screen.
     *
     * @return void
     */
    public function testCompanyDeactivating()
    {
        $attributes = factory(Company::class)->raw(['user_id' => $this->user->id]);

        $company = app('company.repository')->create($attributes);

        $this->putJson(url("api/companies/deactivate/{$company->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNull($company->refresh()->activated_at);

        $this->getJson(url('api/quotes/step/1'))
            ->assertOk()
            ->assertJsonMissing(['id' => $company->id]);
    }

    /**
     * Test deleting a newly created Company.
     *
     * @return void
     */
    public function testCompanyDeleting()
    {
        $attributes = factory(Company::class)->raw(['user_id' => $this->user->id]);

        $company = app('company.repository')->create($attributes);

        $this->deleteJson(url("api/companies/{$company->id}"), [])
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test Deleting System Defined Company.
     *
     * @return void
     */
    public function testSystemCompanyDeleting()
    {
        $systemCompany = app('company.repository')->random(1, fn (Builder $query) => $query->system());

        $this->deleteJson(url("api/companies/{$systemCompany->id}"), [])
            ->assertForbidden()
            ->assertJsonFragment([
                'message' => CPSD_01
            ]);
    }

    /**
     * Test Default Company Vendor assigning
     *
     * @return void
     */
    public function testDefaultCompanyVendor()
    {
        $company = app('company.repository')->create(factory(Company::class)->raw());

        $vendor = $company->vendors->random();

        $response = $this->patchJson("api/companies/{$company->getKey()}", ['default_vendor_id' => $vendor->getKey()])->assertOk();

        $this->assertEquals($vendor->id, $response->json('vendors.0.id'));
    }

    /**
     * Test Default Company Vendor on first import step.
     * 
     * @return void
     */
    public function testDefaultCompanyVendorOnFirstImportStep()
    {
        $company = app('company.repository')->create(factory(Company::class)->raw());

        $vendor = $company->vendors->random();

        $company->update(['default_vendor_id' => $vendor->getKey()]);

        $response = $this->getJson('api/quotes/step/1')->assertOk();

        $firstVendor = collect($response->json('companies'))->firstWhere('id', $company->getKey());

        $this->assertEquals($vendor->id, data_get($firstVendor, 'vendors.0.id'));
    }

    /**
     * Test Default Company Country assigning.
     * 
     * @return void
     */
    public function testDefaultCompanyCountry()
    {
        $attributes = factory(Company::class)->raw();

        $attributes['default_vendor_id'] = Arr::random($attributes['vendors']);

        $company = app('company.repository')->create($attributes);

        $country = $company->defaultVendor->countries->random();

        $this->patchJson(url("api/companies/{$company->id}"), ['default_country_id' => $country->id])->assertOk();

        $response = $this->getJson(url("api/companies/{$company->id}"))->assertOk();

        $this->assertEquals($country->id, $response->json('vendors.0.countries.0.id'));
    }

    /**
     * Test Default Company Country on first import step.
     * 
     * @return void
     */
    public function testDefaultCompanyCountryOnFirstImportStep()
    {
        $attributes = factory(Company::class)->raw();

        $attributes['default_vendor_id'] = Arr::random($attributes['vendors']);

        $company = app('company.repository')->create($attributes);

        $country = $company->defaultVendor->countries->random();

        $company->update(['default_country_id' => $country->getKey()]);

        $response = $this->getJson('api/quotes/step/1')->assertOk();

        $responseCompany = collect($response->json('companies'))->firstWhere('id', $company->getKey());

        $vendor = collect($responseCompany['vendors'])->firstWhere('id', $company->defaultVendor->getKey());

        $this->assertIsArray($vendor, "Company ID: {$company->getKey()}, Default Vendor ID: {$company->defaultVendor->getKey()}");

        $this->assertEquals($country->getKey(), $vendor['countries'][0]['id']);
    }
}
