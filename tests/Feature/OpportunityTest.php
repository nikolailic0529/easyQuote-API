<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

        $response = $this->getJson('api/companies/' . $primaryAccountID)
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

        $response = $this->getJson('api/companies/' . $primaryAccountID)
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

        $this->patchJson('api/opportunities/' . $opportunity->getKey(), $data)
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

                "suppliers_grid" => [
                    "*" => [
                        "id", "supplier_name", "country_name", "contact_name", "contact_email"
                    ]
                ]
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

        $this->deleteJson('api/opportunities/' . $opportunity->getKey())
            ->assertNoContent();

        $this->getJson('api/opportunities/' . $opportunity->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to batch upload the opportunities from a file.
     *
     * @return void
     */
    public function testCanBatchUploadOpportunities()
    {
        $file = UploadedFile::fake()->createWithContent('ops-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/export-23032021.xlsx')));

        $this->authenticateApi();

        $this->postJson('api/opportunities/upload', [
            'file' => $file
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount', 'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date', 'sale_action_name'
                    ]
                ],
                'errors'
            ]);

//        $this->getJson('api/users')
//            ->dump();
    }

    /**
     * Test an ability to batch upload and save the opportunities from a file.
     *
     * @return void
     */
    public function testCanBatchSaveOpportunities()
    {
        $this->app['db.connection']->table('opportunities')->delete();

        $file = UploadedFile::fake()->createWithContent('ops-export.xlsx', file_get_contents(base_path('tests/Feature/Data/opportunity/ops-export.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'file' => $file
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
            $response = $this->getJson('api/opportunities?page=' . $page)
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
}
