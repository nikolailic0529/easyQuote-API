<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Quote\WorldwideQuote;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class OpportunityTest
 * @group worldwide
 */
class OpportunityTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Test an ability to view paginated opportunities.
     *
     * @return void
     */
    public function testCanViewPaginatedOpportunities()
    {
        $this->authenticateApi();

        factory(Opportunity::class, 30)
            ->create([
                'user_id' => $this->app['auth.driver']->id(),
            ]);

        $this->getJson('api/opportunities')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'opportunity_type',
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

        $this->getJson('api/opportunities?order_by_account_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_project_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_account_manager_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_start_date=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_end_date=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_closing_date=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_amount=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_sale_action_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_created_at=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_status=asc')->assertOk();
    }

    /**
     * Test an ability to view own opportunity entities.
     *
     * @return void
     */
    public function testCanViewOwnPaginatedOpportunities()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_opportunities');

        /** @var User $user */
        $user = factory(User::class)->create();

        $user->syncRoles($role);

        $opportunity = factory(Opportunity::class)->create([
            'user_id' => $user->getKey()
        ]);

        factory(Opportunity::class)->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/opportunities')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id'
                    ]
                ]
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
    }

    /**
     * Test an ability to view own lost opportunity entities.
     *
     * @return void
     */
    public function testCanViewOwnPaginatedLostOpportunities()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_opportunities');

        /** @var User $user */
        $user = factory(User::class)->create();

        $user->syncRoles($role);

        $opportunity = factory(Opportunity::class)->create([
            'user_id' => $user->getKey(),
            'status' => 0 // lost
        ]);

        factory(Opportunity::class)->create([
            'status' => 0
        ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/opportunities/lost')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id'
                    ]
                ]
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($user->getKey(), $response->json('data.0.user_id'));
    }

    /**
     * Test an ability to view paginated opportunities with the status 'LOST'.
     *
     * @return void
     */
    public function testCanViewPaginatedLostOpportunities()
    {
        $this->authenticateApi();

        factory(Opportunity::class, 30)
            ->create([
                'user_id' => $this->app['auth.driver']->id(),
                'status' => 0, // 'LOST',
                'status_reason' => Str::random(40)
            ]);

        $this->getJson('api/opportunities/lost')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'opportunity_type',
                        'account_name',
                        'account_manager_name',
                        'project_name',
                        'opportunity_amount',
                        'opportunity_start_date',
                        'opportunity_end_date',
                        'opportunity_closing_date',

                        'status',
                        'status_reason',

                        'sale_action_name',
                        'status',
                        'status_reason',
                        'created_at',
                    ],
                ],
            ]);

        $this->getJson('api/opportunities?order_by_account_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_project_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_account_manager_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_start_date=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_end_date=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_closing_date=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_opportunity_amount=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_sale_action_name=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_created_at=asc')->assertOk();
        $this->getJson('api/opportunities?order_by_status=asc')->assertOk();
    }

    /**
     * Test an ability to view an existing opportunity entity.
     *
     * @return void
     */
    public function testCanViewOpportunity()
    {
        /** @var Company $primaryAccount */
        $primaryAccount = factory(Company::class)->create();

        $primaryAccount->vendors()->sync(Vendor::query()->limit(2)->get());

        /** @var Opportunity $opportunity */
        $opportunity = factory(Opportunity::class)->create([
            'primary_account_id' => $primaryAccount->getKey()
        ]);

        factory(OpportunitySupplier::class, 2)->create(['opportunity_id' => $opportunity->getKey()]);

        $this->authenticateApi();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                "id",
                "user_id",
                "contract_type_id",
                "contract_type",
                "primary_account_id",
                "primary_account",
                "primary_account" => [
                  "vendor_ids"
                ],
                "primary_account_contact_id",
                "primary_account_contact",
                "account_manager_id",
                "account_manager",
                "project_name",
                "nature_of_service",
                "renewal_month",
                "renewal_year",
                "customer_status",
                "end_user_name",
                "hardware_status",
                "region_name",
                "opportunity_start_date",
                "opportunity_end_date",
                "opportunity_closing_date",
                "expected_order_date",
                "customer_order_date",
                "purchase_order_date",
                "supplier_order_date",
                "supplier_order_transaction_date",
                "supplier_order_confirmation_date",
                "opportunity_amount",
                "opportunity_amount_currency_code",
                "purchase_price",
                "purchase_price_currency_code",
                "list_price",
                "list_price_currency_code",
                "estimated_upsell_amount",
                "estimated_upsell_amount_currency_code",
                "margin_value",
                "personal_rating",
                "ranking",
                "account_manager_name",
                "service_level_agreement_id",
                "sale_unit_name",
                "drop_in",
                "lead_source_name",
                "has_higher_sla",
                "is_multi_year",
                "has_additional_hardware",
                "remarks",
                "notes",
                "campaign_name",
                "sale_action_name",
                "competition_name",
                "updated_at",
                "created_at",

                "status",
                "status_reason",

                "base_list_price",
                "base_purchase_price",
                "base_opportunity_amount",

                "is_opportunity_start_date_assumed",
                "is_opportunity_end_date_assumed",

                "suppliers_grid" => [
                    "*" => [
                        "id", "supplier_name", "country_name", "contact_name", "contact_email"
                    ]
                ]
            ]);
    }

    /**
     * Test an ability to create a new opportunity.
     *
     * @returtn void
     */
    public function testCanCreateOpportunity()
    {
        $this->authenticateApi();

        $account = tap(new Company(), function (Company $company) {
            $company->name = $this->faker->company;
            $company->vat = Str::random(40);
            $company->type = 'External';
            $company->email = $this->faker->companyEmail;
            $company->phone = $this->faker->e164PhoneNumber;
            $company->website = $this->faker->url;

            $company->save();

            $contact = factory(Contact::class)->create();

            $company->contacts()->sync($contact);

            $address = factory(Address::class)->create();

            $company->addresses()->sync($address);
        });

        $response = $this->getJson('api/external-companies')
//            ->dump()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'vat',
                        'email',
                        'phone',
                    ],
                ],
            ])
            ->assertOk();

        $primaryAccountID = $response->json('data.0.id');

        $response = $this->getJson('api/companies/'.$primaryAccountID)
//            ->dump()
            ->assertJsonStructure([
                'contacts' => [
                    '*' => [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                        'phone',
                        'mobile',
                        'job_title',
                        'is_verified',
                    ],
                ],
            ])
            ->assertOk();

        $primaryAccountContactID = $response->json('contacts.0.id');

        $data = factory(Opportunity::class)->raw([
            'primary_account_id' => $primaryAccountID,
            'primary_account_contact_id' => $primaryAccountContactID,
        ]);

        $data['suppliers_grid'] = factory(OpportunitySupplier::class, 10)->raw();

        $this->postJson('api/opportunities', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                "id",
                "user_id",
                "contract_type_id",
                "contract_type",
                "primary_account_id",
                "primary_account" => [
                    "id",
                    "addresses" => [
                        "*" => [
                            "id",
                            "is_default"
                        ]
                    ],
                    "contacts" => [
                        "*" => [
                            "id",
                            "is_default"
                        ]
                    ]
                ],
                "primary_account_contact_id",
                "primary_account_contact",
                "account_manager_id",
                "account_manager",
                "project_name",
                "nature_of_service",
                "renewal_month",
                "renewal_year",
                "customer_status",
                "end_user_name",
                "hardware_status",
                "region_name",
                "opportunity_start_date",
                "opportunity_end_date",
                "opportunity_closing_date",
                "expected_order_date",
                "customer_order_date",
                "purchase_order_date",
                "supplier_order_date",
                "supplier_order_transaction_date",
                "supplier_order_confirmation_date",
                "opportunity_amount",
                "opportunity_amount_currency_code",
                "purchase_price",
                "purchase_price_currency_code",
                "list_price",
                "list_price_currency_code",
                "estimated_upsell_amount",
                "estimated_upsell_amount_currency_code",
                "margin_value",
                "personal_rating",
                "ranking",
                "account_manager_name",
                "service_level_agreement_id",
                "sale_unit_name",
                "drop_in",
                "lead_source_name",
                "has_higher_sla",
                "is_multi_year",
                "has_additional_hardware",
                "remarks",
                "sale_action_name",
                "updated_at",
                "created_at",

                "status",
                "status_reason",

                "base_list_price",
                "base_purchase_price",
                "base_opportunity_amount",

                "is_opportunity_start_date_assumed",
                "is_opportunity_end_date_assumed",

                "suppliers_grid" => [
                    "*" => [
                        "id", "supplier_name", "country_name", "contact_name", "contact_email"
                    ]
                ]
            ]);
    }

    /**
     * Test an ability to update an existing opportunity.
     *
     * @return void
     */
    public function testCanUpdateOpportunity()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create([
            'user_id' => $this->app['auth.driver']->id(),
        ]);

        $account = tap(new Company(), function (Company $company) {
            $company->name = $this->faker->company;
            $company->vat = Str::random(40);
            $company->type = 'External';
            $company->email = $this->faker->companyEmail;
            $company->phone = $this->faker->e164PhoneNumber;
            $company->website = $this->faker->url;

            $company->save();

            $contact = factory(Contact::class)->create();

            $company->contacts()->sync($contact);

            $address = factory(Address::class)->create();

            $company->addresses()->sync($address);
        });

        $response = $this->getJson('api/external-companies')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'vat',
                        'email',
                        'phone',
                    ],
                ],
            ])
            ->assertOk();

        $primaryAccountID = $response->json('data.0.id');

        $response = $this->getJson('api/companies/'.$primaryAccountID)
            ->assertJsonStructure([
                'contacts' => [
                    '*' => [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                        'phone',
                        'mobile',
                        'job_title',
                        'is_verified',
                    ],
                ],
            ])
            ->assertOk();

        $primaryAccountContactID = $response->json('contacts.0.id');

        $data = factory(Opportunity::class)->raw([
            'primary_account_id' => $primaryAccountID,
            'primary_account_contact_id' => $primaryAccountContactID,
        ]);

        $data['suppliers_grid'] = factory(OpportunitySupplier::class, 10)->raw();

        $response = $this->patchJson('api/opportunities/'.$opportunity->getKey(), $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                "id",
                "user_id",
                "contract_type_id",
                "contract_type",
                "primary_account_id",
                "primary_account",
                "primary_account_contact_id",
                "primary_account_contact",
                "account_manager_id",
                "account_manager",
                "project_name",
                "nature_of_service",
                "renewal_month",
                "renewal_year",
                "customer_status",
                "end_user_name",
                "hardware_status",
                "region_name",
                "opportunity_start_date",
                "opportunity_end_date",
                "opportunity_closing_date",
                "expected_order_date",
                "customer_order_date",
                "purchase_order_date",
                "supplier_order_date",
                "supplier_order_transaction_date",
                "supplier_order_confirmation_date",
                "opportunity_amount",
                "opportunity_amount_currency_code",
                "purchase_price",
                "purchase_price_currency_code",
                "list_price",
                "list_price_currency_code",
                "estimated_upsell_amount",
                "estimated_upsell_amount_currency_code",
                "margin_value",
                "personal_rating",
                "ranking",
                "account_manager_name",
                "service_level_agreement_id",
                "sale_unit_name",
                "drop_in",
                "lead_source_name",
                "has_higher_sla",
                "is_multi_year",
                "has_additional_hardware",
                "remarks",
                "sale_action_name",
                "updated_at",
                "created_at",

                "status",
                "status_reason",

                "base_list_price",
                "base_purchase_price",
                "base_opportunity_amount",

                "is_opportunity_start_date_assumed",
                "is_opportunity_end_date_assumed",

                "suppliers_grid" => [
                    "*" => [
                        "id", "supplier_name", "country_name", "contact_name", "contact_email"
                    ]
                ]
            ]);

        $this->assertNotEmpty($response->json('primary_account_contact_id'));

        // Test the primary account contact is detached from opportunity,
        // once it's detached from the corresponding primary account.
        $newContactOfPrimaryAccount = factory(Contact::class)->create();

        $this->patchJson('api/companies/'.$primaryAccountID, [
            'name' => $account->name,
            'vat_type' => 'NO VAT',
            'contacts' => [
                ['id' => $newContactOfPrimaryAccount->getKey()]
            ]
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk();

        $this->assertEmpty($response->json('primary_account_contact_id'));
    }

    /**
     * Test an ability to delete an existing opportunity.
     *
     * @return void
     */
    public function testCanDeleteOpportunity()
    {
        $this->authenticateApi();

        $opportunity = factory(Opportunity::class)->create([
            'user_id' => $this->app['auth.driver']->id(),
        ]);

        $this->deleteJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to batch upload the opportunities from a file.
     *
     * @return void
     */
    public function testCanBatchUploadOpportunities()
    {
        $accountsDataFile = UploadedFile::fake()->createWithContent('Accounts-04042021.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/Accounts-04042021.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('Contacts-04042021.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/Contacts-04042021.xlsx')));

        $opportunitiesFile = UploadedFile::fake()->createWithContent('Opportunities-04042021.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/Opportunities-04042021.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
            'accounts_data_file' => $accountsDataFile,
            'account_contacts_file' => $accountContactsFile
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name'
                    ]
                ],
                'errors'
            ]);

        $this->assertEmpty($response->json('errors'));
    }

    /**
     * Test an ability to batch upload the opportunities from a file without accounts data file.
     *
     * @return void
     */
    public function testCanBatchUploadOpportunitiesWithoutAccountsDataFiles()
    {
        $opportunitiesFile = UploadedFile::fake()->createWithContent('Opportunities-04042021.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/Opportunities-04042021.xlsx')));

        $this->authenticateApi();

        $this->app['db.connection']->table('companies')->where('type', 'External')->delete();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name'
                    ]
                ],
                'errors'
            ]);

        $this->assertNotEmpty($response->json('errors'));
    }

    /**
     * Test an ability to batch upload and save the opportunities from a file.
     *
     * @return void
     */
    public function testCanBatchSaveOpportunities()
    {
        $this->app['db.connection']->table('opportunities')->update(['deleted_at' => now()]);

        $opportunitiesFile = UploadedFile::fake()->createWithContent('ops-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/export-23032021.xlsx')));

        $accountsDataFile = UploadedFile::fake()->createWithContent('primary-account-contacts-pipeliner-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/primary-account-data-pipeliner-export.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('primary-account-contacts-pipeliner-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/primary-account-contacts-pipeliner-export.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
            'accounts_data_file' => $accountsDataFile,
            'account_contacts_file' => $accountContactsFile
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date'
                    ]
                ],
                'errors'
            ]);

        $keys = $response->json('opportunities.*.id');

        // Ensure that uploaded opportunities don't exist on the main listing.
        $response = $this->getJson('api/opportunities')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id']
                ]
            ]);

        $keysOnListing = $response->json('data.*.id');

        $this->assertEmpty($keysOnListing);

        $this->patchJson('api/opportunities/save', [
            'opportunities' => $keys
        ])
//            ->dump()
            ->assertNoContent();

        $keysOnListing = [];
        $page = 1;

        // Ensure that uploaded opportunities don't exist on the main listing.

        do {
            $response = $this->getJson('api/opportunities?page='.$page)
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id']
                    ]
                ]);

            $keysOnListing = array_merge($keysOnListing, $response->json('data.*.id'));

            $page++;
        } while (null !== $response->json('links.next'));

        $this->assertCount(count($keys), $keysOnListing);
    }

    /**
     * Test an ability to mark an existing opportunity as lost.
     *
     * @return void
     */
    public function testCanMarkOpportunityAsLost()
    {
        $opportunity = factory(Opportunity::class)->create([
            'status' => 1,
            'status_reason' => null
        ]);

        $this->authenticateApi();

        $this->patchJson('api/opportunities/'.$opportunity->getKey().'/lost', [
            'status_reason' => $statusReason = 'Hardware is no longer covered'
        ])
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJson([
                'status' => 0,
                'status_reason' => $statusReason
            ]);
    }

    /**
     * Test an ability to restore an existing opportunity,
     * i.e. mark an opportunity as not lost.
     *
     * @return void
     */
    public function testCanRestoreOpportunity()
    {
        $opportunity = factory(Opportunity::class)->create([
            'status' => 1,
            'status_reason' => null
        ]);

        $this->authenticateApi();

        $this->patchJson('api/opportunities/'.$opportunity->getKey().'/lost', [
            'status_reason' => $statusReason = 'Hardware is no longer covered'
        ])
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJson([
                'status' => 0,
                'status_reason' => $statusReason
            ]);

        $this->patchJson('api/opportunities/'.$opportunity->getKey().'/restore-from-lost')
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJson([
                'status' => 1,
                'status_reason' => null
            ]);
    }

    /**
     * Test an ability to delete opportunity with attached quote.
     *
     * @return void
     */
    public function testCanNotDeleteOpportunityWithAttachedQuote()
    {
        $opportunity = factory(Opportunity::class)->create();

        $quote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey()
        ]);

        $this->authenticateApi();

        $this->deleteJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertForbidden();
    }
}
