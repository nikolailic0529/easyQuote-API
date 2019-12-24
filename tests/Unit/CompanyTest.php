<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};
use Str, Arr;

class CompanyTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, AssertsListing;

    /**
     * Test Company listing.
     *
     * @return void
     */
    public function testCompanyListing()
    {
        $response = $this->getJson(url('api/companies'), $this->authorizationHeader);

        $this->assertListing($response);
    }

    /**
     * Test Company creating with valid attributes.
     *
     * @return void
     */
    public function testCompanyCreating()
    {
        $attributes = $this->makeGenericCompanyAttributes();

        $response = $this->postJson(url('api/companies'), $attributes, $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonStructure(array_keys($attributes));
    }

    /**
     * Test Company creating with already existing company vat.
     *
     * @return void
     */
    public function testCompanyCreatingWithExistingVat()
    {
        $attributes = $this->makeGenericCompanyAttributes();

        $existingVat = app('company.repository')->random()->vat;

        data_set($attributes, 'vat', $existingVat);

        $response = $this->postJson(url('api/companies'), $attributes, $this->authorizationHeader);

        $response->assertJsonValidationErrors(['vat']);
    }

    /**
     * Test a newly created Company updating.
     *
     * @return void
     */
    public function testCompanyUpdating()
    {
        $attributes = $this->makeGenericCompanyAttributes();

        data_set($attributes, 'user_id', $this->user->id);

        $company = app('company.repository')->create($attributes);

        $newAttributes = array_merge(
            $this->makeGenericCompanyAttributes(),
            ['_method' => 'PATCH']
        );

        $response = $this->postJson(url("api/companies/{$company->id}"), $newAttributes, $this->authorizationHeader);

        $response->assertOk()
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
        $attributes = $this->makeGenericCompanyAttributes();

        data_set($attributes, 'user_id', $this->user->id);

        $company = app('company.repository')->create($attributes);

        $response = $this->putJson(url("api/companies/activate/{$company->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $company->refresh();

        $this->assertNotNull($company->activated_at);

        $response = $this->getJson(url('api/quotes/step/1'), $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonFragment(['id' => $company->id]);
    }

    /**
     * Test deactivating a newly created Company and ensure that company doesn't appear on Quote Import screen.
     *
     * @return void
     */
    public function testCompanyDeactivating()
    {
        $attributes = $this->makeGenericCompanyAttributes();

        data_set($attributes, 'user_id', $this->user->id);

        $company = app('company.repository')->create($attributes);

        $response = $this->putJson(url("api/companies/deactivate/{$company->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $company->refresh();

        $this->assertNull($company->activated_at);

        $response = $this->getJson(url('api/quotes/step/1'), $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonMissing(['id' => $company->id]);
    }

    /**
     * Test deleting a newly created Company.
     *
     * @return void
     */
    public function testCompanyDeleting()
    {
        $attributes = $this->makeGenericCompanyAttributes();

        data_set($attributes, 'user_id', $this->user->id);

        $company = app('company.repository')->create($attributes);

        $response = $this->deleteJson(url("api/companies/{$company->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test Deleting System Defined Company.
     *
     * @return void
     */
    public function testSystemCompanyDeleting()
    {
        $systemCompany = app('company.repository')->random(1, function (Builder $query) {
            $query->system();
        });

        $response = $this->deleteJson(url("api/companies/{$systemCompany->id}"), [], $this->authorizationHeader);

        $response->assertForbidden()
            ->assertJsonFragment([
                'message' => CPSD_01
            ]);
    }

    protected function makeGenericCompanyAttributes()
    {
        return [
            'name' => $this->faker->company,
            'vat' => Str::random(10),
            'type' => 'Internal',
            'email' => $this->faker->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'website' => $this->faker->url,
            'vendors' => app('vendor.repository')->random(2)->pluck('id')->toArray()
        ];
    }
}
