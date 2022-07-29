<?php

namespace Tests\Feature;

use App\Enum\CustomerTypeEnum;
use App\Models\Address;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\{Arr, Str};
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
        $this->getJson('api/companies')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'default_vendor_id',
                        'default_country_id',
                        'default_template_id',
                        'is_system',
                        'name',
                        'short_code',
                        'type',
                        'category',
                        'source',
                        'source_long',
                        'vat',
                        'email',
                        'phone',
                        'website',
                        'logo',
                        'total_quoted_value',
                        'created_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);


        $query = Arr::query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_vat' => 'asc',
            'order_by_phone' => 'asc',
            'order_by_website' => 'asc',
            'order_by_type' => 'asc',
            'order_by_category' => 'asc',
        ]);

        $this->getJson('api/companies?'.$query)
            ->assertOk();

//        $this->getJson('api/companies?'.Arr::query([
//            'search' => 'Premier Tech Eau et Environnement'
//            ]))
//            ->dump();
    }

    /**
     * Test an ability to view paginated companies filtered by category.
     *
     * @return void
     */
    public function testCanViewExternalCompaniesFilteredByCategory()
    {
        $categories = [
            'End User',
            'Reseller',
            'Business Partner',
        ];

        foreach ($categories as $category) {
            Company::factory()->create([
                'type' => 'External',
                'category' => $category,
            ]);
        }

        foreach ($categories as $category) {

            $response = $this->getJson('api/external-companies/?filter[category][]='.$category)
//                ->dump()
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'type',
                            'category',
                        ],
                    ],
                ]);

            foreach ($response->json('data.*.category') as $value) {
                $this->assertSame($category, $value);
            }
        }
    }

    /**
     * Test an ability to view company form data.
     *
     * @return void
     */
    public function testCanViewCompanyFormData()
    {
        $response = $this->getJson('api/companies/create')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'types',
                'categories',
                'sources',
                'vendors' => [
                    '*' => [
                        'id', 'name', 'logo',
                    ],
                ],
            ]);

        foreach ([
                     'Internal', 'External',
                 ] as $type) {
            $this->assertContains($type, $response->json('types'));
        }

        foreach ([
                     'End User',
                     'Reseller',
                     'Business Partner',
                 ] as $category) {
            $this->assertContains($category, $response->json('categories'));
        }

        foreach ([
                     'S4',
                     'EQ',
                     'Pipeliner',
                 ] as $source) {
            $this->assertContains($source, $response->json('sources'));
        }
    }

    /**
     * Test an ability to view company form data from opportunity screen.
     *
     * @return void
     */
    public function testCanViewCompanyFormDataFromOpportunityScreen()
    {
        $response = $this->getJson('api/companies/create')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'types',
                'categories',
                'sources',
                'vendors' => [
                    '*' => [
                        'id', 'name', 'logo',
                    ],
                ],
            ]);

        $this->assertContains('End User', $response->json('categories'));

        $response = $this->getJson('api/companies/create?correlation_id=3f40afc9-20ca-4374-b882-0716970b8f4c')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'types',
                'categories',
                'sources',
                'vendors' => [
                    '*' => [
                        'id', 'name', 'logo',
                    ],
                ],
            ]);

        $this->assertNotContains('End User', $response->json('categories'));
    }

    /**
     * Test an ability to create a new company.
     *
     * @return void
     */
    public function testCanCreateCompany()
    {
        $attributes = Company::factory()->raw();

        $attributes['vendors'] = factory(Vendor::class, 2)->create()->modelKeys();
        $attributes['addresses'] = array_map(fn(string $id) => ['id' => $id], factory(Address::class, 2)->create()->modelKeys());
        $attributes['contacts'] = array_map(fn(string $id) => ['id' => $id], Contact::factory()->count(2)->create()->modelKeys());

        $this->postJson('api/companies', $attributes)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure(array_keys($attributes));
    }

    /**
     * Test an ability to create a new company with non-unique name.
     */
    public function testCanNotCreateCompanyWithNonUniqueName(): void
    {
        $existingCompany = Company::factory()->create();

        $attributes = Company::factory()->raw([
            'name' => $existingCompany->name,
        ]);
        $attributes['vendors'] = factory(Vendor::class, 2)->create()->modelKeys();
        $attributes['addresses'] = array_map(fn(string $id) => ['id' => $id], factory(Address::class, 2)->create()->modelKeys());
        $attributes['contacts'] = array_map(fn(string $id) => ['id' => $id], Contact::factory()->count(2)->create()->modelKeys());


        $this->postJson('api/companies', $attributes)
//            ->dump()
            ->assertUnprocessable()
            ->assertInvalid(['name'], responseKey: 'Error.original');
    }

    /**
     * Test an ability to create a new company without vat attributes.
     *
     * @return void
     */
    public function testCanCreateCompanyWithoutVatAttributes()
    {
        $attributes = Company::factory()->raw();

        unset($attributes['vat']);
        unset($attributes['vat_type']);

        $attributes['vendors'] = factory(Vendor::class, 2)->create()->modelKeys();
        $attributes['addresses'] = array_map(fn(string $id) => ['id' => $id], factory(Address::class, 2)->create()->modelKeys());
        $attributes['contacts'] = array_map(fn(string $id) => ['id' => $id], Contact::factory()->count(2)->create()->modelKeys());

        $this->postJson('api/companies', $attributes)
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
        $company = Company::factory()->create();

        $attributes = Company::factory()->raw(['vat' => $company->vat]);

        $this->postJson('api/companies', $attributes)
            ->assertStatus(422)
            ->assertJsonStructure([
                'Error' => ['original' => ['vat']],
            ]);
    }

    /**
     * Test an ability to update an existing company.
     *
     * @return void
     */
    public function testCanUpdateCompany(): void
    {
        $company = Company::factory()->create();

        $newAttributes = Company::factory()->raw([
            '_method' => 'PATCH',
            'customer_type' => $this->faker->randomElement(CustomerTypeEnum::cases())->value,
        ]);

        $machineAddress = factory(Address::class)->create(['address_type' => 'Machine']);
        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);

        $contact1 = Contact::factory()->create();
        $contact2 = Contact::factory()->create();

        $newAttributes['vendors'] = factory(Vendor::class, 2)->create()->modelKeys();

        $newAttributes['addresses'] = [
            ['id' => $machineAddress->getKey(), 'is_default' => "1"],
            ['id' => $invoiceAddress->getKey(), 'is_default' => "0"],
        ];

        $newAttributes['contacts'] = [
            ['id' => $contact1->getKey(), 'is_default' => "1"],
            ['id' => $contact2->getKey(), 'is_default' => "0"],
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
                        'is_default',
                    ],
                ],
                'contacts' => [
                    '*' => [
                        'id',
                        'is_default',
                    ],
                ],
            ]);

        $response = $this->getJson('api/companies/'.$company->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'customer_type',
                'addresses' => [
                    '*' => [
                        'id',
                        'is_default',
                    ],
                ],
                'contacts' => [
                    '*' => [
                        'id',
                        'is_default',
                    ],
                ],
            ]);

        $machineAddressFromResponse = Arr::first($response->json('addresses'), fn(array $address) => $address['id'] === $machineAddress->getKey());
        $contact1FromResponse = Arr::first($response->json('contacts'), fn(array $contact) => $contact['id'] === $contact1->getKey());


        $this->assertTrue($machineAddressFromResponse['is_default']);
        $this->assertTrue($contact1FromResponse['is_default']);

        $assertableAttributes = Arr::except($newAttributes, [
            '_method',
            'vendors',
            'addresses',
            'contacts',
            'logo'
        ]);

        foreach ($assertableAttributes as $attribute => $value) {
            $this->assertSame($value, $response->json($attribute));
        }
    }

    /**
     * Test an ability to change source of the company with `FROZEN_SOURCE` flag set.
     */
    public function testCanNotChangeSourceOfCompanyWithFrozenSourceFlag(): void
    {
        /** @var Company $company */
        $company = Company::factory()->create([
            'flags' => Company::FROZEN_SOURCE,
            'source' => 'Pipeliner',
        ]);

        $this->patchJson('api/companies/'.$company->getKey(), [
            'name' => $company->name,
            'type' => 'External',
            'vat_type' => 'NO VAT',
            'category' => 'Reseller',
            'source' => 'EQ'
        ])
            ->assertInvalid([
                'source' => 'Forbidden to change source of the company.'
            ], responseKey: 'Error.original');

        $this->patchJson('api/companies/'.$company->getKey(), [
            'name' => $company->name,
            'type' => 'Internal',
            'vat_type' => 'NO VAT',
            'category' => 'Reseller',
            'source' => 'EQ'
        ])
            ->assertInvalid([
                'source' => 'Forbidden to change source of the company.'
            ], responseKey: 'Error.original');
    }

    /**
     * Test an ability to detach used in quotes addresses from an existing company.
     *
     * @return void
     */
    public function testCanDetachUsedAddressesFromCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        $newAttributes = Company::factory()->raw(['_method' => 'PATCH']);

        $usedMachineAddress = factory(Address::class)->create(['address_type' => 'Machine']);
        $machineAddress = factory(Address::class)->create(['address_type' => 'Machine']);
        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create();

        $packAsset = factory(WorldwideQuoteAsset::class)->create([
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'machine_address_id' => $usedMachineAddress->getKey(),
        ]);

        $company->addresses()->syncWithoutDetaching($usedMachineAddress->getKey());

        $contact1 = Contact::factory()->create();
        $contact2 = Contact::factory()->create();

        $newAttributes['vendors'] = factory(Vendor::class, 2)->create()->modelKeys();

        $newAttributes['addresses'] = [
            ['id' => $machineAddress->getKey(), 'is_default' => "1"],
            ['id' => $invoiceAddress->getKey(), 'is_default' => "0"],
        ];

        $newAttributes['contacts'] = [
            ['id' => $contact1->getKey(), 'is_default' => "1"],
            ['id' => $contact2->getKey(), 'is_default' => "0"],
        ];

        $newAttributes['logo'] = UploadedFile::fake()->createWithContent('company-logo.jpg', file_get_contents(base_path('tests/Feature/Data/images/epd.png')));

        $response = $this->postJson("api/companies/".$company->getKey(), $newAttributes)
//            ->dump()
            ->assertForbidden()
            ->assertJsonStructure([
                'message'
            ]);

        $this->assertStringContainsString('You cannot detach some of the addresses, used in the quotes', $response->json('message'));
    }

    /**
     * Test an ability to update an existing company.
     *
     * @return void
     */
    public function testCanPartiallyUpdateCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        $company->addresses()->attach(factory(Address::class)->create());

        $newAttributes = [
            'name' => $company->name,
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->e164PhoneNumber(),
            'website' => Str::random(40),
            '_method' => 'PATCH',
        ];

        $machineAddress = factory(Address::class)->create(['address_type' => 'Machine']);
        $invoiceAddress = factory(Address::class)->create(['address_type' => 'Invoice']);

        $contact1 = Contact::factory()->create();
        $contact2 = Contact::factory()->create();

        $newAttributes['addresses'] = [
            ['id' => $machineAddress->getKey(), 'is_default' => "1"],
            ['id' => $invoiceAddress->getKey(), 'is_default' => "0"],
        ];

        $newAttributes['contacts'] = [
            ['id' => $contact1->getKey(), 'is_default' => "1"],
            ['id' => $contact2->getKey(), 'is_default' => "0"],
        ];

        $newAttributes['logo'] = UploadedFile::fake()->createWithContent('company-logo.jpg', file_get_contents(base_path('tests/Feature/Data/images/epd.png')));

        $this->postJson("api/companies/partial/".$company->getKey(), $newAttributes)
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
                        'is_default',
                    ],
                ],
                'contacts' => [
                    '*' => [
                        'id',
                        'is_default',
                    ],
                ],
            ]);

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'addresses' => [
                    '*' => [
                        'id',
                        'is_default',
                    ],
                ],
                'contacts' => [
                    '*' => [
                        'id',
                        'is_default',
                    ],
                ],
            ]);

        $machineAddressFromResponse = Arr::first($response->json('addresses'), fn(array $address) => $address['id'] === $machineAddress->getKey());
        $contact1FromResponse = Arr::first($response->json('contacts'), fn(array $contact) => $contact['id'] === $contact1->getKey());


        $this->assertTrue($machineAddressFromResponse['is_default']);
        $this->assertTrue($contact1FromResponse['is_default']);

        foreach (Arr::except($newAttributes, ['logo', '_method', 'addresses', 'contacts']) as $key => $value) {
            $this->assertSame($value, $response->json($key));
        }
    }

    /**
     * Test an ability to update the specified contact of a company.
     */
    public function testCanUpdateCompanyContact()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        /** @var Contact $contact */

        $company->contacts()->sync($contact = Contact::factory()->create());

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
                'is_verified',
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
                        'is_verified',
                    ],
                ],
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
        $company = tap(Company::factory()->create(), function (Company $company) {

            $company->activated_at = null;

            $company->save();

        });

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson("api/companies/activate/".$company->getKey(), [])
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
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
        $company = tap(Company::factory()->create(), function (Company $company) {

            $company->activated_at = now();

            $company->save();

        });

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson("api/companies/deactivate/".$company->getKey(), [])
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/companies/'.$company->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
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
        $company = Company::factory()->create();

        $this->deleteJson("api/companies/".$company->getKey(), [])
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test an ability to delete an existing company attached to an opportunity as primary account.
     *
     * @return void
     */
    public function testCanNotDeleteCompanyAttachedToOpportunityAsPrimaryAccount()
    {
        $company = Company::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'primary_account_id' => $company->getKey(),
        ]);

        $response = $this->deleteJson("api/companies/".$company->getKey(), [])
//            ->dump()
            ->assertForbidden()
            ->assertJsonStructure([
                'message',
            ]);

        $this->assertStringStartsWith('You can not delete the company', $response->json('message'));
    }

    /**
     * Test an ability tot delete system defined company.
     *
     * @return void
     */
    public function testCanNotDeleteSystemDefinedCompany()
    {
        $systemCompany = Company::factory()->create(['flags' => Company::SYSTEM]);

        $this->deleteJson("api/companies/".$systemCompany->getKey(), [])
            ->assertForbidden()
            ->assertJsonFragment([
                'message' => CPSD_01,
            ]);
    }

    /**
     * Test Default Company Vendor assigning
     *
     * @return void
     */
    public function testCanUpdateDefaultCompanyVendor()
    {
        $company = Company::factory()->create();
        $company->vendors()->sync($vendor = factory(Vendor::class)->create());

        $attributes = Company::factory()->raw([
            'default_vendor_id' => $vendor->getKey(),
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
        $company = Company::factory()->create();
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
        $company = Company::factory()->create([
            'short_code' => Str::random(3),
        ]);

        $company->vendors()->sync($vendor = factory(Vendor::class)->create());

        $response = $this->getJson('api/quotes/step/1?prioritize[company]='.$company->short_code)
            ->assertOk()
            ->assertJsonStructure([
                'companies' => [
                    '*' => [
                        'id', 'short_code',
                    ],
                ],
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

        $company = Company::factory()->create();

        $country = $vendor->countries->random();

        $attributes = Company::factory()->raw([
            'vendors' => [$vendor->getKey()],
            'default_vendor_id' => $vendor->getKey(),
            'default_country_id' => $country->getKey(),
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

        $company = Company::factory()->create();

        $country = $vendor->countries->random();

        $attributes = Company::factory()->raw([
            'vendors' => [$vendor->getKey()],
            'default_vendor_id' => $vendor->getKey(),
            'default_country_id' => $country->getKey(),
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

    /**
     * Test an ability to view a list of existing opportunities of the specified company.
     *
     * @return void
     */
    public function testCanViewListOfOpportunitiesOfCompany()
    {
        $company = Company::factory()->create();

        Opportunity::factory()->count(10)->create([
            'primary_account_id' => $company->getKey(),
        ]);

        $response = $this->getJson('api/companies/'.$company->getKey().'/opportunities')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'opportunity_type',
                        'status_type',
                        'account_name',
                        'account_manager_name',
                        'project_name',
                        'opportunity_amount',
                        'opportunity_start_date',
                        'opportunity_end_date',
                        'opportunity_closing_date',
                        'sale_action_name',
                        'status',
                        'status_reason',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * Test an ability to view a list of existing quotes of the specified company.
     *
     * @return void
     */
    public function testCanViewListOfQuotesOfCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        /** @var Customer $rescueCustomer */
        $rescueCustomer = factory(Customer::class)->create([
            'company_reference_id' => $company->getKey(),
            'name' => $company->name,
        ]);

        $rescueQuote = factory(Quote::class)->create([
            'customer_id' => $rescueCustomer->getKey(),
        ]);

        // Rescue Quote entity of another Customer.
        factory(Quote::class)->create();

        $worldwideOpportunity = Opportunity::factory()->create([
            'primary_account_id' => $company->getKey(),
        ]);

        $worldwideQuote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $worldwideOpportunity->getKey(),
        ]);

        // Worldwide Quote entity of another Customer.
        factory(WorldwideQuote::class)->create();

        $response = $this->getJson('api/companies/'.$company->getKey().'/quotes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'business_division',
                        'contract_type',
                        'opportunity_id',
                        'customer_id',
                        'customer_name',
                        'company_name',
                        'rfq_number',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'active_version_id',

                        'has_distributor_files',
                        'has_schedule_files',

                        'sales_order_id',
                        'has_sales_order',
                        'sales_order_submitted',
                        'contract_id',
                        'has_contract',
                        'contract_submitted_at',

                        'status_type',
                        'submission_status',

                        'permissions' => [
                            'view',
                            'update',
                            'delete',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertContains($rescueQuote->getKey(), $response->json('data.*.id'));
        $this->assertContains($worldwideQuote->getKey(), $response->json('data.*.id'));
    }

    public function testCanViewListOfQuotesOfCompanyWithoutSuperPermissions()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(
            'view_companies',
            'view_quotes',
            'view_own_ww_quotes'
        );

        /** @var User $user */
        $user = User::factory()->create();

        $user->syncRoles($role);

        $this->actingAs($user, 'api');

        /** @var Company $company */
        $company = Company::factory()->create();

        /** @var Customer $rescueCustomer */
        $rescueCustomer = factory(Customer::class)->create([
            'company_reference_id' => $company->getKey(),
            'name' => $company->name,
        ]);

        $rescueQuote = factory(Quote::class)->create([
            'user_id' => $user->getKey(),
            'customer_id' => $rescueCustomer->getKey(),
        ]);

        // Rescue Quote entity of another Customer.
        factory(Quote::class)->create();

        $worldwideOpportunity = Opportunity::factory()->create([
            'primary_account_id' => $company->getKey(),
        ]);

        /** @var WorldwideQuote $worldwideQuote */
        $worldwideQuote = factory(WorldwideQuote::class)->create([
            'user_id' => $user->getKey(),
            'opportunity_id' => $worldwideOpportunity->getKey(),
        ]);

        // Worldwide Quote entity of another Customer.
        factory(WorldwideQuote::class)->create();

        $response = $this->getJson('api/companies/'.$company->getKey().'/quotes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'business_division',
                        'contract_type',
                        'opportunity_id',
                        'customer_id',
                        'customer_name',
                        'company_name',
                        'rfq_number',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'active_version_id',

                        'has_distributor_files',
                        'has_schedule_files',

                        'sales_order_id',
                        'has_sales_order',
                        'sales_order_submitted',
                        'contract_id',
                        'has_contract',
                        'contract_submitted_at',

                        'status_type',
                        'submission_status',

                        'permissions' => [
                            'view',
                            'update',
                            'delete',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertContains($rescueQuote->getKey(), $response->json('data.*.id'));
        $this->assertContains($worldwideQuote->getKey(), $response->json('data.*.id'));
    }

    /**
     * Test an ability to view a list of existing sales orders of the specified company.
     *
     * @return void
     */
    public function testCanViewListOfSalesOrdersOfCompany()
    {
        $company = Company::factory()->create();

        /** @var SalesOrder $salesOrderOfCustomer */
        $salesOrderOfCustomer = factory(SalesOrder::class)->create();

        $salesOrderOfCustomer->worldwideQuote->opportunity->update([
            'primary_account_id' => $company->getKey(),
        ]);

        /** @var SalesOrder $salesOrderOfAnotherCustomer */
        $salesOrderOfAnotherCustomer = factory(SalesOrder::class)->create();

        $response = $this->getJson('api/companies/'.$company->getKey().'/sales-orders')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'contract_type_id',
                        'worldwide_quote_id',
                        'opportunity_id',
                        'order_number',
                        'order_date',
                        'status',
                        'failure_reason',
                        'status_reason',
                        'customer_name',
                        'rfq_number',
                        'order_type',
                        'permissions' => [
                            'view',
                            'update',
                            'delete',
                        ],
                        'created_at',
                        'updated_at',
                        'submitted_at',
                        'activated_at',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertContains($salesOrderOfCustomer->getKey(), $response->json('data.*.id'));
    }

    /**
     * Test an ability to view a list of existing unified notes of the specified company.
     *
     * @return void
     */
    public function testCanViewListOfUnifiedNotesOfCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        /** @var Customer $rescueCustomer */
        $rescueCustomer = factory(Customer::class)->create([
            'company_reference_id' => $company->getKey(),
            'name' => $company->name,
        ]);

        /** @var Quote $rescueQuote */
        $rescueQuote = factory(Quote::class)->create([
            'customer_id' => $rescueCustomer->getKey(),
        ]);

        $rescueQuoteNote = Note::factory()
            ->hasAttached($rescueQuote, relationship: 'rescueQuotesHaveNote')
            ->create();

        $worldwideOpportunity = Opportunity::factory()->create([
            'primary_account_id' => $company->getKey(),
        ]);

        /** @var WorldwideQuote $worldwideQuote */
        $worldwideQuote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $worldwideOpportunity->getKey(),
        ]);

        $worldwideQuoteNote = Note::factory()
            ->hasAttached($worldwideQuote, relationship: 'worldwideQuotesHaveNote')
            ->create();

        $companyNote = Note::factory()
            ->hasAttached($company, relationship: 'companiesHaveNote')
            ->create();

        $response = $this->getJson('api/companies/'.$company->getKey().'/notes')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'note_entity_type',
                        'note_entity_class',
                        'parent_entity_type',
                        'quote_id',
                        'customer_id',
                        'quote_number',
                        'text',
                        'owner_user_id',
                        'owner_fullname',
                        'permissions' => [
                            'update',
                            'delete',
                        ],
                        'created_at',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertContains($rescueQuoteNote->getKey(), $response->json('data.*.id'));
        $this->assertContains($worldwideQuoteNote->getKey(), $response->json('data.*.id'));
        $this->assertContains($companyNote->getKey(), $response->json('data.*.id'));
        $this->assertContains($rescueQuote->customer->rfq, $response->json('data.*.quote_number'));
        $this->assertContains($worldwideQuote->quote_number, $response->json('data.*.quote_number'));
    }

    /**
     * Test an ability to view a list of existing assets of the specified company.
     *
     * @return void
     */
    public function testCanViewListOfAssetsOfCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        /** @var Asset $companyAsset */
        $companyAsset = factory(Asset::class)->create();

        $company->assets()->attach($companyAsset);

        $anotherAsset = factory(Asset::class)->create();

        $response = $this->getJson('api/companies/'.$company->getKey().'/assets')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_number',
                        'serial_number',
                        'product_image',
                        'asset_category_name',
                        'base_warranty_start_date',
                        'base_warranty_end_date',
                        'active_warranty_start_date',
                        'active_warranty_end_date',
                        'created_at',
                        'permissions' => [
                            'view',
                            'update',
                            'delete',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertCount(1, $response->json('data'));
        $this->assertContains($companyAsset->getKey(), $response->json('data.*.id'));
    }

    /**
     * Test an ability to view a list of existing attachments of company.
     *
     * @return void
     */
    public function testCanViewListOfAttachmentsOfCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        $companyAttachments = factory(Attachment::class, 2)->create();

        $company->attachments()->sync($companyAttachments);

        /** @var Customer $rescueCustomer */
        $rescueCustomer = factory(Customer::class)->create([
            'company_reference_id' => $company->getKey(),
            'name' => $company->name,
        ]);

        /** @var Quote $rescueQuote */
        $rescueQuote = factory(Quote::class)->create([
            'customer_id' => $rescueCustomer->getKey(),
        ]);
        $rescueQuoteAttachments = factory(Attachment::class, 2)->create();
        $rescueQuote->attachments()->sync($rescueQuoteAttachments);


        /** @var Quote $anotherRescueQuote */
        $anotherRescueQuote = factory(Quote::class)->create();
        $anotherRescueQuoteAttachments = factory(Attachment::class, 2)->create();
        $anotherRescueQuote->attachments()->sync($anotherRescueQuoteAttachments);


        $worldwideOpportunity = Opportunity::factory()->create([
            'primary_account_id' => $company->getKey(),
        ]);

        /** @var WorldwideQuote $worldwideQuote */
        $worldwideQuote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $worldwideOpportunity->getKey(),
        ]);
        $worldwideQuoteAttachments = factory(Attachment::class, 2)->create();
        $worldwideQuote->attachments()->sync($worldwideQuoteAttachments);


        /** @var WorldwideQuote $anotherWorldwideQuote */
        $anotherWorldwideQuote = factory(WorldwideQuote::class)->create();
        $anotherWorldwideQuoteAttachments = factory(Attachment::class, 2)->create();
        $anotherWorldwideQuote->attachments()->sync($anotherWorldwideQuoteAttachments);


        $response = $this->getJson('api/companies/'.$company->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'parent_entity_class',
                        'parent_entity_type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $response->assertJsonCount(6, 'data');
        $this->assertCount(2, array_filter($response->json('data'), fn(array $attachment) => $attachment['parent_entity_type'] === 'Company'));
        $this->assertCount(4, array_filter($response->json('data'), fn(array $attachment) => $attachment['parent_entity_type'] === 'Quote'));

        foreach ($companyAttachments as $attachment) {

            $this->assertContains($attachment->getKey(), $response->json('data.*.id'));

        }

        foreach ($rescueQuoteAttachments as $attachment) {

            $this->assertContains($attachment->getKey(), $response->json('data.*.id'));

        }

        foreach ($worldwideQuoteAttachments as $attachment) {

            $this->assertContains($attachment->getKey(), $response->json('data.*.id'));

        }
    }

    /**
     * Test an ability to create a new attachment for company.
     *
     * @return void
     */
    public function testCanCreateNewAttachmentForCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        $file = UploadedFile::fake()->create(Str::random(40).'.txt', 1_000);

        $response = $this->postJson('api/companies/'.$company->getKey().'/attachments', [
            'type' => 'Maintenance Contract',
            'file' => $file,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'type',
                'filepath',
                'filename',
                'size',
                'created_at',
            ]);

        $attachmentID = $response->json('id');

        $response = $this->getJson('api/companies/'.$company->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertContains($attachmentID, $response->json('data.*.id'));
    }

    /**
     * Test an ability to delete an existing attachment of company.
     *
     * @return void
     */
    public function testCanDeleteAttachmentOfCompany()
    {
        /** @var Company $company */
        $company = Company::factory()->create();

        $attachment = factory(Attachment::class)->create();

        $company->attachments()->attach($attachment);

        $response = $this->getJson('api/companies/'.$company->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertContains($attachment->getKey(), $response->json('data.*.id'));

        $this->deleteJson('api/companies/'.$company->getKey().'/attachments/'.$attachment->getKey())
            ->assertNoContent();

        $response = $this->getJson('api/companies/'.$company->getKey().'/attachments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'filepath',
                        'filename',
                        'extension',
                        'size',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertEmpty($response->json('data'));
    }
}
