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
            'user_id' => $user->getKey(),
        ]);

        factory(Opportunity::class)->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/opportunities')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
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
            'status' => 0,
        ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/opportunities/lost')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
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
                'status_reason' => Str::random(40),
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
            'primary_account_id' => $primaryAccount->getKey(),
        ]);

        factory(OpportunitySupplier::class, 2)->create(['opportunity_id' => $opportunity->getKey()]);

        $this->authenticateApi();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                "id",
                "user_id",
                "pipeline_id",
                "pipeline",
                "contract_type_id",
                "contract_type",
                "primary_account_id",
                "end_user_id",
                "primary_account",
                "primary_account" => [
                    "vendor_ids",
                ],
                "end_user",
                "are_end_user_addresses_available",
                "are_end_user_contacts_available",
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
                "has_service_credits",
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
                        "id", "supplier_name", "country_name", "contact_name", "contact_email",
                    ],
                ],
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

        $endUser = factory(Company::class)->create();

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
            'end_user_id' => $endUser->getKey(),
            'is_contract_duration_checked' => false,
            'are_end_user_addresses_available' => true,
            'are_end_user_contacts_available' => true,
        ]);

        $data['suppliers_grid'] = factory(OpportunitySupplier::class, 10)->raw();

        $response = $this->postJson('api/opportunities', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                "id",
                "user_id",
                "pipeline_id",
                "pipeline",
                "contract_type_id",
                "contract_type",
                "primary_account_id",
                "end_user_id",
                "end_user" => [
                    "id",
                    "addresses" => [
                        "*" => [
                            "id",
                            "is_default",
                        ],
                    ],
                    "contacts" => [
                        "*" => [
                            "id",
                            "is_default",
                        ],
                    ],
                ],
                "are_end_user_addresses_available",
                "are_end_user_contacts_available",
                "primary_account" => [
                    "id",
                    "addresses" => [
                        "*" => [
                            "id",
                            "is_default",
                        ],
                    ],
                    "contacts" => [
                        "*" => [
                            "id",
                            "is_default",
                        ],
                    ],
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
                "is_contract_duration_checked",
                "contract_duration_months",
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
                "has_service_credits",
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
                        "id", "supplier_name", "country_name", "contact_name", "contact_email",
                    ],
                ],
            ]);

        $this->getJson('api/opportunities/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'primary_account_id',
                'end_user_id',
            ])
            ->assertJson([
                'primary_account_id' => $primaryAccountID,
                'end_user_id' => $endUser->getKey(),
                'are_end_user_addresses_available' => true,
                'are_end_user_contacts_available' => true,
            ]);
    }

    /**
     * Test an ability to create a new opportunity with contract duration.
     *
     * @returtn void
     */
    public function testCanCreateOpportunityWithContractDuration()
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
            'is_contract_duration_checked' => true,
            'contract_duration_months' => 2,
        ]);

        $data['suppliers_grid'] = factory(OpportunitySupplier::class, 10)->raw();

        $response = $this->postJson('api/opportunities', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                "id",
                "user_id",
                "pipeline_id",
                "pipeline",
                "contract_type_id",
                "contract_type",
                "primary_account_id",
                "primary_account" => [
                    "id",
                    "addresses" => [
                        "*" => [
                            "id",
                            "is_default",
                        ],
                    ],
                    "contacts" => [
                        "*" => [
                            "id",
                            "is_default",
                        ],
                    ],
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
                "is_contract_duration_checked",
                "contract_duration_months",
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
                "has_service_credits",
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
                        "id", "supplier_name", "country_name", "contact_name", "contact_email",
                    ],
                ],
            ]);

        $this->getJson('api/opportunities/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'contract_duration_months',
                'is_contract_duration_checked',
            ])
            ->assertJson([
                'contract_duration_months' => 2,
                'is_contract_duration_checked' => true,
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

        $endUser = factory(Company::class)->create();

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
            'end_user_id' => $endUser->getKey(),
            'is_contract_duration_checked' => false,
            'are_end_user_addresses_available' => true,
            'are_end_user_contacts_available' => true,
        ]);

        $data['suppliers_grid'] = factory(OpportunitySupplier::class, 10)->raw();

        $response = $this->patchJson('api/opportunities/'.$opportunity->getKey(), $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                "id",
                "user_id",
                "pipeline_id",
                "pipeline",
                "contract_type_id",
                "contract_type",
                "primary_account_id",
                "primary_account",
                "end_user_id",
                "end_user",
                "are_end_user_addresses_available",
                "are_end_user_contacts_available",
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
                "is_contract_duration_checked",
                "contract_duration_months",
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
                "has_service_credits",
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
                        "id", "supplier_name", "country_name", "contact_name", "contact_email",
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('primary_account_contact_id'));

        // Test the primary account contact is detached from opportunity,
        // once it's detached from the corresponding primary account.
        $newContactOfPrimaryAccount = factory(Contact::class)->create();

        $this->patchJson('api/companies/'.$primaryAccountID, [
            'name' => $account->name,
            'vat_type' => 'NO VAT',
            'contacts' => [
                ['id' => $newContactOfPrimaryAccount->getKey()],
            ],
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertJsonStructure([
                'id',
                'primary_account_id',
                'end_user_id',
                'primary_account_contact_id',
            ])
            ->assertOk()
            ->assertJson([
                'primary_account_id' => $primaryAccountID,
                'end_user_id' => $endUser->getKey(),
                'are_end_user_addresses_available' => true,
                'are_end_user_contacts_available' => true,

            ]);

        $this->assertEmpty($response->json('primary_account_contact_id'));
    }

    /**
     * Test an ability to update an existing opportunity with contract duration.
     *
     * @return void
     */
    public function testCanUpdateOpportunityWithContractDuration()
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
            'is_contract_duration_checked' => true,
            'contract_duration_months' => 60,
        ]);

        $data['suppliers_grid'] = factory(OpportunitySupplier::class, 10)->raw();

        $response = $this->patchJson('api/opportunities/'.$opportunity->getKey(), $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                "id",
                "user_id",
                "pipeline_id",
                "pipeline",
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
                "is_contract_duration_checked",
                "contract_duration_months",
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
                "has_service_credits",
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
                        "id", "supplier_name", "country_name", "contact_name", "contact_email",
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('primary_account_contact_id'));

        // Test the primary account contact is detached from opportunity,
        // once it's detached from the corresponding primary account.
        $newContactOfPrimaryAccount = factory(Contact::class)->create();

        $this->patchJson('api/companies/'.$primaryAccountID, [
            'name' => $account->name,
            'vat_type' => 'NO VAT',
            'contacts' => [
                ['id' => $newContactOfPrimaryAccount->getKey()],
            ],
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk();

        $this->assertEmpty($response->json('primary_account_contact_id'));

        $response = $this->getJson('api/opportunities/'.$opportunity->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'contract_duration_months', 'is_contract_duration_checked',
            ])
            ->assertJson([
                'contract_duration_months' => 60,
                'is_contract_duration_checked' => true,
            ]);
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
            'account_contacts_file' => $accountContactsFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
            ]);

        $this->assertEmpty($response->json('errors'));
    }

    /**
     * Test an ability to batch upload the opportunities from a file.
     *
     * @return void
     */
    public function testCanBatchUploadOpportunitiesBy20211102()
    {
        $accountsDataFile = UploadedFile::fake()->createWithContent('accounts-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/Accounts-04042021.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('contacts-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/Contacts-04042021.xlsx')));

        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/Opportunities-04042021.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
            'accounts_data_file' => $accountsDataFile,
            'account_contacts_file' => $accountContactsFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
            ]);

        $this->assertEmpty($response->json('errors'));
    }

    /**
     * Test an ability to batch upload the opportunities from a file.
     * The new fields added: Reseller(yes/no), End User(yes/no), Vendor(Lenovo, IBM).
     */
    public function testCanUploadOpportunitiesBy20220121(): void
    {
        $accountsDataFile = UploadedFile::fake()->createWithContent('accounts-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/accounts-21012022.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('contacts-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/contacts-21012022.xlsx')));

        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/opps-21012022.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
            'accounts_data_file' => $accountsDataFile,
            'account_contacts_file' => $accountContactsFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
            ]);

        $this->assertNotEmpty($response->json('opportunities'));

        $this->assertEmpty($response->json('errors'));

        $this->patchJson('api/opportunities/save', [
            'opportunities' => $response->json('opportunities.*.id'),
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/opportunities/'.$response->json('opportunities.0.id'))
//            ->dump()
            ->assertJsonStructure([
                'id',
                'primary_account' => [
                    'id', 'name',
                    'vendors' => [
                        '*' => ['id', 'name', 'short_code',],
                    ],
                    'addresses' => [
                        '*' => ['id', 'address_type', 'address_1', 'city', 'state', 'post_code', 'address_2', 'country_id'],
                    ],
                    'contacts' => [
                        '*' => ['id', 'contact_type', 'first_name', 'last_name'],
                    ],
                ],
                'end_user' => [
                    'id', 'name',
                ],
                'primary_account_contact' => [
                    'id', 'first_name', 'last_name',
                ],
            ]);

        $this->assertSame('Foster and Partners', $response->json('primary_account.name'));
        $this->assertSame('Foster and Partners', $response->json('end_user.name'));
        $this->assertSame($response->json('primary_account.id'), $response->json('end_user.id'));

        $this->assertSame('Mehdi', $response->json('primary_account_contact.first_name'));
        $this->assertSame('DOUMBIA', $response->json('primary_account_contact.last_name'));

        $this->assertCount(2, $response->json('primary_account.vendors'));
        $this->assertContains('LEN', $response->json('primary_account.vendors.*.short_code'));
        $this->assertContains('IBM', $response->json('primary_account.vendors.*.short_code'));

        $this->assertSame('Invoice', $response->json('primary_account.addresses.0.address_type'));
        $this->assertSame('Verseci u. 1-15', $response->json('primary_account.addresses.0.address_1'));
        $this->assertSame('Székesfehérvár,', $response->json('primary_account.addresses.0.city'));
        $this->assertSame('London', $response->json('primary_account.addresses.0.state'));
        $this->assertSame('8000', $response->json('primary_account.addresses.0.post_code'));
        $this->assertNull($response->json('primary_account.addresses.0.address_2'));
        $this->assertSame('HU', $response->json('primary_account.addresses.0.country.iso_3166_2'));
    }

    /**
     * Test an ability to batch upload the opportunities from a file without accounts & contacts file.
     * The new fields added: Reseller(yes/no), End User(yes/no), Vendor(Lenovo, IBM).
     */
    public function testCanUploadOpportunitiesBy20220121WithoutAccountsAndContactsFile(): void
    {
        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/opps-21012022.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
            ]);
    }

    /**
     * Test an ability to batch upload the opportunities from a file.
     */
    public function testCanUploadOpportunitiesBy20220125(): void
    {
        $existingCompany = factory(Company::class)->create([
            'name' => 'AT Company 5',
            'type' => 'External'
        ]);

        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps-0211.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/opps-25012022.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
            ]);

        $this->assertNotEmpty($response->json('opportunities'));

        $this->assertEmpty($response->json('errors'));

        $this->patchJson('api/opportunities/save', [
            'opportunities' => $response->json('opportunities.*.id'),
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/opportunities/'.$response->json('opportunities.0.id'))
//            ->dump()
            ->assertJsonStructure([
                'id',
                'primary_account' => [
                    'id', 'name',
                    'vendors' => [
                        '*' => ['id', 'name', 'short_code',],
                    ],
                    'addresses' => [
                        '*' => ['id', 'address_type', 'address_1', 'city', 'state', 'post_code', 'address_2', 'country_id'],
                    ],
                    'contacts' => [
                        '*' => ['id', 'contact_type', 'first_name', 'last_name'],
                    ],
                ],
                'suppliers_grid' => [
                    '*' => [
                        'supplier_name',
                        'country_name',
                        'contact_name',
                        'contact_email',
                    ]
                ]
            ]);

        $this->assertNotNull($response->json('primary_account'));
        $this->assertSame('AT Company 5', $response->json('primary_account.name'));
        $this->assertCount(1, $response->json('suppliers_grid'));
        $this->assertSame('Orion', $response->json('suppliers_grid.0.supplier_name'));
        $this->assertSame('United Kingdom', $response->json('suppliers_grid.0.country_name'));
        $this->assertSame('John Bricknell, Solid Systems', $response->json('suppliers_grid.0.contact_name'));
        $this->assertSame('john.bricknell@solid-global.com', $response->json('suppliers_grid.0.contact_email'));
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
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
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
        $this->app['db.connection']->table('companies')->where('is_system', false)->update(['deleted_at' => now()]);

        $opportunitiesFile = UploadedFile::fake()->createWithContent('ops-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/export-23032021.xlsx')));

        $accountsDataFile = UploadedFile::fake()->createWithContent('primary-account-contacts-pipeliner-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/primary-account-data-pipeliner-export.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('primary-account-contacts-pipeliner-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/primary-account-contacts-pipeliner-export.xlsx')));

        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_opportunities',
            'create_opportunities',
            'update_own_opportunities',
            'delete_own_opportunities',
        ]);

        /** @var User $user */
        $user = factory(User::class)->create();

        $this->authenticateApi($user);

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
            'accounts_data_file' => $accountsDataFile,
            'account_contacts_file' => $accountContactsFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date',
                    ],
                ],
                'errors',
            ]);

        $keys = $response->json('opportunities.*.id');

        // Ensure that uploaded opportunities don't exist on the main listing.
        $response = $this->getJson('api/opportunities')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id'],
                ],
            ]);

        $keysOnListing = $response->json('data.*.id');

        $this->assertEmpty($keysOnListing);

        $this->patchJson('api/opportunities/save', [
            'opportunities' => $keys,
        ])
//            ->dump()
            ->assertNoContent();

        $keysOnListing = [];
        $page = 1;

        // Assert that uploaded opportunities don't exist on the main listing.
        do {
            $response = $this->getJson('api/opportunities?page='.$page)
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id'],
                    ],
                ]);

            $keysOnListing = array_merge($keysOnListing, $response->json('data.*.id'));

            $page++;
        } while (null !== $response->json('links.next'));

        $this->assertCount(count($keys), $keysOnListing);

        $response = $this->getJson('api/opportunities')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id'],
                ],
            ]);

        // Assert that user has permissions for a newly created company.
        $opportunityWithCompany = collect($response->json('data'))->whereNotNull('company_id')->first();

        $this->assertNotNull($opportunityWithCompany);

        $companyResponse = $this->getJson('api/companies/'.$opportunityWithCompany['company_id'])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'addresses' => [
                    '*' => ['id', 'user_id'],
                ],
                'contacts' => [
                    '*' => ['id', 'user_id'],
                ],
                'permissions' => [
                    'view',
                    'update',
                    'delete',
                ],
            ]);

        $this->assertTrue($companyResponse->json('permissions.view'));
        $this->assertTrue($companyResponse->json('permissions.update'));
        $this->assertTrue($companyResponse->json('permissions.delete'));

        foreach ($companyResponse->json('addresses.*.user_id') as $ownerID) {
            $this->assertSame($user->getKey(), $ownerID);
        }

        foreach ($companyResponse->json('contacts.*.user_id') as $ownerID) {
            $this->assertSame($user->getKey(), $ownerID);
        }
    }


    /**
     * Test an ability to see matched own primary account in imported opportunity,
     * when multiple companies are present with the same name owned by different users.
     *
     */
    public function testCanSeeMatchedOwnPrimaryAccountInImportedOpportunity(): void
    {
        $this->app['db.connection']->table('opportunities')->delete();
        $this->app['db.connection']->table('companies')->where('is_system', false)->delete();

        $opportunitiesFile = UploadedFile::fake()->createWithContent('ops-17012022.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/ops-17012022.xlsx')));

        $accountsDataFile = UploadedFile::fake()->createWithContent('accounts-17012022.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/accounts-17012022.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('contacts-17012022.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/contacts-17012022.xlsx')));

        $otherUserCompany = factory(Company::class)->create([
            'user_id' => factory(User::class)->create()->getKey(),
            'name' => 'ASA Computer',
            'type' => 'External',
        ]);

        $this->authenticateApi();

        $ownCompany = factory(Company::class)->create([
            'user_id' => $this->app['auth.driver']->id(),
            'name' => 'ASA Computer',
            'type' => 'External',
        ]);

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
            'accounts_data_file' => $accountsDataFile,
            'account_contacts_file' => $accountContactsFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date',
                    ],
                ],
                'errors',
            ]);

        $this->assertEmpty($response->json('errors'));
        $this->assertCount(1, $response->json('opportunities'));

        $this->patchJson('api/opportunities/save', [
            'opportunities' => $response->json('opportunities.*.id'),
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/opportunities/')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));

        $response = $this->getJson('api/opportunities/'.$response->json('data.0.id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'primary_account' => [
                    'id', 'user_id',
                ],
            ]);

        $this->assertSame($this->app['auth.driver']->id(), $response->json('user_id'));
        $this->assertSame($this->app['auth.driver']->id(), $response->json('primary_account.user_id'));
        $this->assertSame($ownCompany->getKey(), $response->json('primary_account.id'));
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
            'status_reason' => null,
        ]);

        $this->authenticateApi();

        $this->patchJson('api/opportunities/'.$opportunity->getKey().'/lost', [
            'status_reason' => $statusReason = 'Hardware is no longer covered',
        ])
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJson([
                'status' => 0,
                'status_reason' => $statusReason,
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
            'status_reason' => null,
        ]);

        $this->authenticateApi();

        $this->patchJson('api/opportunities/'.$opportunity->getKey().'/lost', [
            'status_reason' => $statusReason = 'Hardware is no longer covered',
        ])
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJson([
                'status' => 0,
                'status_reason' => $statusReason,
            ]);

        $this->patchJson('api/opportunities/'.$opportunity->getKey().'/restore-from-lost')
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertOk()
            ->assertJson([
                'status' => 1,
                'status_reason' => null,
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
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $this->authenticateApi();

        $this->deleteJson('api/opportunities/'.$opportunity->getKey())
//            ->dump()
            ->assertForbidden();
    }

    /**
     * Test an ability to view opportunity entities grouped by pipeline stages.
     *
     * @return void
     */
    public function testCanViewOpportunitiesGroupedByPipelineStages()
    {
        $this->authenticateApi();

        $this->getJson('api/pipelines/default/stage-opportunity')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'stage_id',
                    'stage_name',
                    'stage_order',
                    'opportunities' => [
                        '*' => [
                            'id',
                            'user_id',
                            'account_manager_id',
                            'primary_account.id',
                            'primary_account.name',
                            'primary_account.phone',
                            'primary_account.email',
                            'project_name',
                            'account_manager_name',
                            'opportunity_closing_date',
                            'opportunity_amount',
                            'base_opportunity_amount',
                            'opportunity_amount_currency_code',
                            'primary_account_contact.first_name',
                            'primary_account_contact.last_name',
                            'primary_account_contact.phone',
                            'primary_account_contact.email',
                            'opportunity_start_date',
                            'opportunity_end_date',
                            'ranking',
                            'status',
                            'status_reason',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    }
}
