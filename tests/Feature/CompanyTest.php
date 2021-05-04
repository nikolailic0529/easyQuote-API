<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Arr, Str};
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing, WithFakeUser};

/**
 * @group build
 */
class CompanyTest extends TestCase
{
    use WithFakeUser, AssertsListing, DatabaseTransactions;

    /**
     * Test an ability to view paginated companies listing.
     *
     * @return void
     */
    public function testCanViewPaginatedCompaniesListing()
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

        $this->getJson(url('api/companies?'.$query))->assertOk();
    }

    /**
     * Test an ability to create a new company.
     *
     * @return void
     */
    public function testCanCreateCompany()
    {
        $attributes = factory(Company::class)->raw();

        $this->postJson(url('api/companies'), $attributes)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure(array_keys($attributes));
    }

    /**
     * Test an ability to create a new company with an existing VAT code.
     *
     * @return void
     */
    public function testCanNotCreateCompanyWithExistingVatCode()
    {
        $company = factory(Company::class)->create();

        $attributes = factory(Company::class)->raw(['vat' => $company->vat]);

        $this->postJson(url('api/companies'), $attributes)
            ->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['vat']]
            ]);
    }

    /**
     * Test an ability to update an existing company.
     *
     * @return void
     */
    public function testCanUpdateCompany()
    {
        $company = factory(Company::class)->create();

        $newAttributes = factory(Company::class)->raw(['_method' => 'PATCH']);

        $machineAddress = factory(Address::class)->create(['address_type' => 'Machine']);
        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);

        $contact1 = factory(Contact::class)->create();
        $contact2 = factory(Contact::class)->create();

        $newAttributes['addresses'] = [
            ['id' => $machineAddress->getKey(), 'is_default' => "1"],
            ['id' => $invoiceAddress->getKey(), 'is_default' => "0"],
        ];

        $newAttributes['contacts'] = [
            ['id' => $contact1->getKey(), 'is_default' => "1"],
            ['id' => $contact2->getKey(), 'is_default' => "0"]
        ];

        $newAttributes['logo'] = UploadedFile::fake()->createWithContent('company-logo.jpg', file_get_contents(base_path('tests/Feature/Data/images/epd.png')));

        $this->postJson("api/companies/".$company->getKey(), $newAttributes)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'short_code',
                'vat',
                'type',
                'email',
                'phone',
                'website',
                'addresses' => [
                    '*' => [
                        'id',
                        'is_default'
                    ]
                ],
                'contacts' => [
                    '*' => [
                        'id',
                        'is_default'
                    ]
                ]
            ]);

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'addresses' => [
                    '*' => [
                        'id',
                        'is_default'
                    ]
                ],
                'contacts' => [
                    '*' => [
                        'id',
                        'is_default'
                    ]
                ]
            ]);

        $machineAddressFromResponse = Arr::first($response->json('addresses'), fn(array $address) => $address['id'] === $machineAddress->getKey());
        $contact1FromResponse = Arr::first($response->json('contacts'), fn(array $contact) => $contact['id'] === $contact1->getKey());


        $this->assertTrue($machineAddressFromResponse['is_default']);
        $this->assertTrue($contact1FromResponse['is_default']);
    }

    /**
     * Test an ability to update the specified contact of a company.
     */
    public function testCanUpdateCompanyContact()
    {
        /** @var Company $company */
        $company = factory(Company::class)->create();

        /** @var Contact $contact */

        $company->contacts()->sync($contact = factory(Contact::class)->create());

        $contactData = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'phone' => $this->faker->phoneNumber,
            'mobile' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'job_title' => $this->faker->jobTitle,
            'is_verified' => $this->faker->boolean,
        ];

        $this->patchJson('api/companies/'.$company->getKey().'/contacts/'.$contact->getKey(), $contactData)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'email',
                'first_name',
                'last_name',
                'phone',
                'mobile',
                'email',
                'job_title',
                'is_verified'
            ]);

        $response = $this->getJson('api/companies/'.$company->getKey())
//            ->dump()
            ->assertJsonStructure([
                'id',
                'contacts' => [
                    '*' => [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                        'phone',
                        'mobile',
                        'email',
                        'job_title',
                        'is_verified'
                    ]
                ]
            ])
            ->assertOk();

        $updatedContactData = Arr::first($response->json('contacts'), fn(array $contactData) => $contactData['id'] === $contact->getKey());

        $this->assertIsArray($updatedContactData);
        $this->assertEquals($contactData['first_name'], $updatedContactData['first_name']);
        $this->assertEquals($contactData['last_name'], $updatedContactData['last_name']);
        $this->assertEquals($contactData['phone'], $updatedContactData['phone']);
        $this->assertEquals($contactData['mobile'], $updatedContactData['mobile']);
        $this->assertEquals($contactData['email'], $updatedContactData['email']);
        $this->assertEquals($contactData['job_title'], $updatedContactData['job_title']);
        $this->assertEquals($contactData['is_verified'], $updatedContactData['is_verified']);
    }

    /**
     * Test an ability to activate an existing company.
     *
     * @return void
     */
    public function testCanActivateCompany()
    {
        $company = tap(factory(Company::class)->create(), function (Company $company) {

            $company->activated_at = null;

            $company->save();

        });

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson("api/companies/activate/".$company->getKey(), [])
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->getJson('api/quotes/step/1')
            ->assertOk()
            ->assertJsonFragment(['id' => $company->getKey()]);
    }

    /**
     * Test an ability to deactivate an existing company.
     *
     * @return void
     */
    public function testCanDeactivateCompany()
    {
        $company = tap(factory(Company::class)->create(), function (Company $company) {

            $company->activated_at = now();

            $company->save();

        });

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson("api/companies/deactivate/".$company->getKey(), [])
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->getJson('api/quotes/step/1')
            ->assertOk()
            ->assertJsonMissing(['id' => $company->getKey()]);
    }

    /**
     * Test an ability to delete an existing company.
     *
     * @return void
     */
    public function testCanDeleteCompany()
    {
        $company = factory(Company::class)->create();

        $this->deleteJson("api/companies/".$company->getKey(), [])
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test an ability tot delete system defined company.
     *
     * @return void
     */
    public function testCanNotDeleteSystemDefinedCompany()
    {
        $systemCompany = factory(Company::class)->create(['is_system' => true]);

        $this->deleteJson("api/companies/".$systemCompany->getKey(), [])
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
    public function testCanUpdateDefaultCompanyVendor()
    {
        $company = factory(Company::class)->create();
        $company->vendors()->sync($vendor = factory(Vendor::class)->create());

        $attributes = factory(Company::class)->raw([
            'default_vendor_id' => $vendor->getKey()
        ]);

        $response = $this->patchJson("api/companies/{$company->getKey()}", $attributes)
//            ->dump()
            ->assertOk();

        $this->assertEquals($vendor->id, $response->json('vendors.0.id'));
    }

    /**
     * Test an ability to view default vendor of the company on the first import step.
     *
     * @return void
     */
    public function testCanViewDefaultCompanyVendorOnFirstImportStep()
    {
        $company = factory(Company::class)->create();
        $company->vendors()->sync($vendor = factory(Vendor::class)->create());

        $company->update(['default_vendor_id' => $vendor->getKey()]);

        $response = $this->getJson('api/quotes/step/1')->assertOk();

        $firstVendor = collect($response->json('companies'))->firstWhere('id', $company->getKey());

        $this->assertEquals($vendor->getKey(), data_get($firstVendor, 'vendors.0.id'));
    }

    /**
     * Test an ability to prioritize company in list on first import step.
     *
     * @return void
     */
    public function testCanViewPrioritizedCompanyOnFirstImportStep()
    {
        /** @var Company $company */
        $company = factory(Company::class)->create([
            'short_code' => Str::random(3)
        ]);

        $company->vendors()->sync($vendor = factory(Vendor::class)->create());

        $response = $this->getJson('api/quotes/step/1?prioritize[company]='.$company->short_code)
            ->assertOk()
            ->assertJsonStructure([
                'companies' => [
                    '*' => [
                        'id', 'short_code'
                    ]
                ]
            ]);

        $this->assertEquals($company->getKey(), $response->json('companies.0.id'));
    }

    /**
     * Test an ability to set default company country.
     *
     * @return void
     */
    public function testCanUpdateDefaultCompanyCountry()
    {
        $vendor = factory(Vendor::class)->create();

        $company = factory(Company::class)->create();

        $country = $vendor->countries->random();

        $attributes = factory(Company::class)->raw([
            'vendors' => [$vendor->getKey()],
            'default_vendor_id' => $vendor->getKey(),
            'default_country_id' => $country->getKey()
        ]);

        $this->patchJson('api/companies/'.$company->getKey(), $attributes)
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/companies/'.$company->getKey())
//            ->dump()
            ->assertOk();

        $this->assertEquals($country->id, $response->json('vendors.0.countries.0.id'));
    }

    /**
     * Test an ability to view default company country on the first import step.
     *
     * @return void
     */
    public function testCanViewDefaultCompanyCountryOnFirstImportStep()
    {
        $vendor = factory(Vendor::class)->create();

        $company = factory(Company::class)->create();

        $country = $vendor->countries->random();

        $attributes = factory(Company::class)->raw([
            'vendors' => [$vendor->getKey()],
            'default_vendor_id' => $vendor->getKey(),
            'default_country_id' => $country->getKey()
        ]);

        $this->patchJson('api/companies/'.$company->getKey(), $attributes)
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/companies/'.$company->getKey())
//            ->dump()
            ->assertOk();

        $response = $this->getJson("api/companies/".$company->getKey())->assertOk();

        $this->assertEquals($country->getKey(), $response->json('vendors.0.countries.0.id'));

        $response = $this->getJson('api/quotes/step/1')->assertOk();

        $responseCompany = collect($response->json('companies'))->firstWhere('id', $company->getKey());

        $vendorFromResponse = collect($responseCompany['vendors'])->firstWhere('id', $vendor->getKey());

        $this->assertIsArray($vendorFromResponse, "Company ID: {$company->getKey()}, Default Vendor ID: {$vendor->getKey()}");

        $this->assertEquals($country->getKey(), $vendorFromResponse['countries'][0]['id']);
    }
}
