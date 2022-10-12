<?php

namespace Tests\Feature;

use App\Enum\DateDayEnum;
use App\Enum\DateMonthEnum;
use App\Enum\DateWeekEnum;
use App\Enum\DayOfWeekEnum;
use App\Enum\OpportunityRecurrenceConditionEnum;
use App\Enum\RecurrenceTypeEnum;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\OpportunityValidationResult;
use App\Models\Permission;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeline\PipelineStage;
use App\Models\Quote\WorldwideQuote;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class OpportunityTest
 * @group worldwide
 * @group opportunity
 */
class OpportunityTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getConnection()->table('opportunities')->delete();
        $this->getConnection()->table('companies')->whereRaw('NOT flags & '.Company::SYSTEM);
    }

    /**
     * Test an ability to view paginated opportunities.
     */
    public function testCanViewPaginatedOpportunities(): void
    {
        $this->authenticateApi();

        Opportunity::factory()->count(10)
            ->for($this->app['auth.driver']->user())
            ->create();

        $this->getJson('api/opportunities')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'opportunity_type',
                        'unit_name',
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
        $this->getJson('api/opportunities?order_by_unit_name=asc')->assertOk();
    }

    public function testCanViewOpportunityFilters(): void
    {
        $this->authenticateApi();

        $this->getJson('api/opportunities/filters')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'label',
                        'type',
                        'parameter',
                        'possible_values' => [
                            '*' => [
                                'label',
                                'value',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function testCanFilterPaginatedOpportunitiesUsingCustomerName(): void
    {
        $this->authenticateApi();

        $op = Opportunity::factory()
            ->for(Company::factory(['name' => 'Test primary account']), relationship: 'primaryAccount')
            ->for(Company::factory(['name' => 'Test end user']), relationship: 'endUser')
            ->create();

        Opportunity::factory()
            ->for(Company::factory(['name' => Str::random(40)]), relationship: 'primaryAccount')
            ->for(Company::factory(['name' => Str::random(40)]), relationship: 'endUser')
            ->create();

        $this->getJson('api/opportunities?'.Arr::query([
                'filter' => [
                    'customer_name' => 'primary',
                ],
            ]))
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
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $op->getKey());

        $this->getJson('api/opportunities?'.Arr::query([
                'filter' => [
                    'customer_name' => 'end user',
                ],
            ]))
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
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $op->getKey());
    }

    public function testCanFilterPaginatedOpportunitiesUsingSalesUnit(): void
    {
        $this->authenticateApi();

        /** @var Opportunity $op */
        $op = Opportunity::factory()
            ->for(SalesUnit::factory())
            ->create();

        Opportunity::factory()
            ->for(SalesUnit::factory())
            ->create();

        $this->getJson('api/opportunities?'.Arr::query([
                'filter' => [
                    'sales_unit_id' => $op->salesUnit->getKey(),
                ],
            ]))
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
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $op->getKey());
    }

    public function testCanFilterPaginatedOpportunitiesAccountManager(): void
    {
        $this->authenticateApi();

        /** @var Opportunity $op */
        $op = Opportunity::factory()
            ->for(User::factory(), relationship: 'accountManager')
            ->create();

        Opportunity::factory()
            ->for(User::factory(), relationship: 'accountManager')
            ->create();

        $this->getJson('api/opportunities?'.Arr::query([
                'filter' => [
                    'account_manager_id' => $op->accountManager->getKey(),
                ],
            ]))
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
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $op->getKey());
    }

    /**
     * Test an ability to paginate opportunities of pipeline stage.
     */
    public function testCanViewPaginatedOpportunitiesOfPipelineStage(): void
    {
        $stage = PipelineStage::query()->first();

        Opportunity::factory()->count(10)->create([
            'pipeline_stage_id' => $stage->getKey(),
        ]);

        $this->authenticateApi();

        $this->getJson('api/pipeline-stages/'.$stage->getKey().'/opportunities?per_page=1')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'account_manager_id',
                        'primary_account_id',
                        'primary_account_contact_id',
                        'end_user_id',
                        'project_name',
                        'account_manager_name',
                        'opportunity_closing_date',
                        'base_opportunity_amount',
                        'opportunity_amount',
                        'opportunity_amount_currency_code',
                        'primary_account_name',
                        'primary_account_phone',
                        'primary_account_contact_first_name',
                        'primary_account_contact_last_name',
                        'primary_account_contact_phone',
                        'primary_account_contact_email',
                        'end_user_name',
                        'end_user_phone',
                        'end_user_email',
                        'opportunity_start_date',
                        'opportunity_end_date',
                        'ranking',
                        'status',
                        'status_reason',
                        'created_at',
                        'quotes_exist',
                        'account_manager',
                        'primary_account',
                        'end_user',
                        'primary_account_contact',
                        'permissions',
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => ['url', 'label', 'active'],
                    ],
                    'path',
                    'per_page',
                    'to',
                    'total',
                    'valid',
                    'invalid',
                    'base_amount',
                ],
            ]);
    }

    /**
     * Test an ability to view own opportunity entities.
     */
    public function testCanViewOwnPaginatedOpportunities(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_opportunities');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        $opportunity = Opportunity::factory()
            ->for($user->salesUnits->first())
            ->for($user)
            ->create();

        Opportunity::factory()
            ->for(SalesUnit::factory())
            ->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/opportunities')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'permissions',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));

        foreach ($response->json('data') as $item) {
            $this->assertTrue($item['permissions']['view']);
//            $this->assertSame($user->getKey(), $item['user_id']);
        }
    }

    /**
     * Test an ability to view own lost opportunity entities.
     */
    public function testCanViewOwnPaginatedLostOpportunities(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_opportunities');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        $opportunity = Opportunity::factory()
            ->for($user)
            ->for($user->salesUnits->first())
            ->create([
                'status' => 0 // lost
            ]);

        Opportunity::factory()->create([
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
     */
    public function testCanViewPaginatedLostOpportunities(): void
    {
        $this->authenticateApi();

        Opportunity::factory()->count(30)
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
     */
    public function testCanViewOpportunity(): void
    {
        /** @var Company $primaryAccount */
        $primaryAccount = Company::factory()->create();

        $primaryAccount->vendors()->sync(Vendor::query()->limit(2)->get());

        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::factory()
            ->for($primaryAccount, relationship: 'primaryAccount')
            ->has(OpportunityValidationResult::factory(), relationship: 'validationResult')
            ->create();

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

                "validation_result" => [
                    "is_passed",
                    "messages",
                ],

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
    public function testCanCreateOpportunity(): void
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

            $contact = Contact::factory()->create();

            $company->contacts()->sync($contact);

            $address = factory(Address::class)->create();

            $company->addresses()->sync($address);
        });

        $endUser = Company::factory()->create();
//        $endUser->forceFill(['activated_at' => null])->save();

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

        $data = Opportunity::factory()->raw([
            'primary_account_id' => $primaryAccountID,
            'primary_account_contact_id' => $primaryAccountContactID,
            'end_user_id' => $endUser->getKey(),
            'is_contract_duration_checked' => false,
            'are_end_user_addresses_available' => true,
            'are_end_user_contacts_available' => true,
            'sale_action_name' => Pipeline::query()->where('is_default',
                1)->sole()->pipelineStages->random()->qualifiedStageName,
        ]);

//        unset($data['pipeline_stage_id']);

        $data['suppliers_grid'] = collect()->times(5, function (int $n) {
            /** @var CustomField $field */
            $field = CustomField::query()->where('field_name', "opportunity_distributor$n")->sole();

            /** @var CustomFieldValue $value */
            $value = $field->values()->whereHas('allowedBy')->first();

            return [
                'supplier_name' => $value->field_value,
                'country_name' => $value->allowedBy->random()->field_value,
                'contact_name' => $this->faker->firstName(),
                'contact_email' => $this->faker->companyEmail(),
            ];
        })->all();

        $response = $this->postJson('api/opportunities', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                "id",
                "user_id",
                "pipeline_id",
                "pipeline",
                "pipeline_stage_id",
                "pipeline_stage",
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
     */
    public function testCanCreateOpportunityWithContractDuration(): void
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

            $contact = Contact::factory()->create();

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

        $data = Opportunity::factory()->raw([
            'primary_account_id' => $primaryAccountID,
            'primary_account_contact_id' => $primaryAccountContactID,
            'is_contract_duration_checked' => true,
            'contract_duration_months' => 2,
            'sale_action_name' => Pipeline::query()->where('is_default',
                1)->sole()->pipelineStages->random()->qualifiedStageName,
        ]);

        $data['suppliers_grid'] = collect()->times(5, function (int $n) {
            /** @var CustomField $field */
            $field = CustomField::query()->where('field_name', "opportunity_distributor$n")->sole();

            /** @var CustomFieldValue $value */
            $value = $field->values()->whereHas('allowedBy')->first();

            return [
                'supplier_name' => $value->field_value,
                'country_name' => $value->allowedBy->random()->field_value,
                'contact_name' => $this->faker->firstName(),
                'contact_email' => $this->faker->companyEmail(),
            ];
        })->all();

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

    public function testCanCreateOpportunityWithRecurrence(): void
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

            $contact = Contact::factory()->create();

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

        $data = Opportunity::factory()->raw([
            'primary_account_id' => $primaryAccountID,
            'primary_account_contact_id' => $primaryAccountContactID,
            'is_contract_duration_checked' => true,
            'contract_duration_months' => 2,
            'sale_action_name' => Pipeline::query()->where('is_default',
                1)->sole()->pipelineStages->random()->qualifiedStageName,
            'recurrence' => [
                'stage_id' => Pipeline::query()->where('is_default', 1)->sole()->pipelineStages->random()->getKey(),
                'condition' => OpportunityRecurrenceConditionEnum::Lost->value | OpportunityRecurrenceConditionEnum::Won->value,
                'type' => $this->faker->randomElement(RecurrenceTypeEnum::cases())->value,
                'occur_every' => $this->faker->numberBetween(1, 99),
                'occurrences_count' => $this->faker->numberBetween(-1, 9999),
                'start_date' => $this->faker->dateTimeBetween('+1day', '+3day')->format('Y-m-d H:i:s'),
                'end_date' => $this->faker->dateTimeBetween('+3day', '+7day')->format('Y-m-d H:i:s'),
                'day' => $this->faker->randomElement(DateDayEnum::cases())->value,
                'month' => $this->faker->randomElement(DateMonthEnum::cases())->value,
                'week' => $this->faker->randomElement(DateWeekEnum::cases())->value,
                'day_of_week' => $this->faker->randomElement([
                    DayOfWeekEnum::Sunday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Thursday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Friday->value,
                    DayOfWeekEnum::Sunday->value | DayOfWeekEnum::Monday->value | DayOfWeekEnum::Tuesday->value | DayOfWeekEnum::Wednesday->value | DayOfWeekEnum::Friday->value | DayOfWeekEnum::Saturday->value,
                ]),
            ],
        ]);

        $data['suppliers_grid'] = collect()->times(5, function (int $n) {
            /** @var CustomField $field */
            $field = CustomField::query()->where('field_name', "opportunity_distributor$n")->sole();

            /** @var CustomFieldValue $value */
            $value = $field->values()->whereHas('allowedBy')->first();

            return [
                'supplier_name' => $value->field_value,
                'country_name' => $value->allowedBy->random()->field_value,
                'contact_name' => $this->faker->firstName(),
                'contact_email' => $this->faker->companyEmail(),
            ];
        })->all();

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
                'recurrence' => [
                    'id',
                    'stage_id',
                    'condition',
                    'user_id',
                    'opportunity_id',
                    'type',
                    'day',
                    'week',
                    'day_of_week',
                    'month',
                    'occur_every',
                    'occurrences_count',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->getJson('api/opportunities/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'recurrence' => [
                    'id',
                    'stage_id',
                    'condition',
                    'user_id',
                    'opportunity_id',
                    'type',
                    'day',
                    'week',
                    'day_of_week',
                    'month',
                    'occur_every',
                    'occurrences_count',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * Test an ability to update an existing opportunity.
     */
    public function testCanUpdateOpportunity(): void
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create([
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

            $contact = Contact::factory()->create();

            $company->contacts()->sync($contact);

            $address = factory(Address::class)->create();

            $company->addresses()->sync($address);
        });

        $endUser = Company::factory()->create();

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

        $data = Opportunity::factory()->raw([
            'primary_account_id' => $primaryAccountID,
            'primary_account_contact_id' => $primaryAccountContactID,
            'end_user_id' => $endUser->getKey(),
            'is_contract_duration_checked' => false,
            'are_end_user_addresses_available' => true,
            'are_end_user_contacts_available' => true,
            'sale_action_name' => Pipeline::query()->where('is_default',
                1)->sole()->pipelineStages->random()->qualifiedStageName,
        ]);

//        unset($data['pipeline_stage_id']);

        $data['suppliers_grid'] = collect()->times(5, function (int $n) {
            /** @var CustomField $field */
            $field = CustomField::query()->where('field_name', "opportunity_distributor$n")->sole();

            /** @var CustomFieldValue $value */
            $value = $field->values()->whereHas('allowedBy')->first();

            return [
                'supplier_name' => $value->field_value,
                'country_name' => $value->allowedBy->random()->field_value,
                'contact_name' => $this->faker->firstName(),
                'contact_email' => $this->faker->companyEmail(),
            ];
        })->all();

        $response = $this->patchJson('api/opportunities/'.$opportunity->getKey(), $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                "id",
                "user_id",
                "pipeline_id",
                "pipeline",
                "pipeline_stage_id",
                "pipeline_stage",
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
        $newContactOfPrimaryAccount = Contact::factory()->create();

        $this->patchJson('api/companies/'.$primaryAccountID, [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'name' => $account->name,
            'vat_type' => 'NO VAT',
            'type' => 'External',
            'source' => 'Pipeliner',
            'category' => 'Reseller',
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
     * Test an ability to update an existing opportunity when the user can update any quote associated with it.
     */
    public function testCanUpdateOpportunityWhenUserCanUpdateAnyQuoteAssociatedWithIt(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->hasAttached(Team::factory(), relationship: 'ledTeams')
            ->hasAttached(Role::factory()->hasAttached(
                Permission::query()
                    ->whereIn('name', [
                        "update_assets",
                        "update_own_opportunities",
                        "view_own_ww_quotes",
                        "view_companies",
                        "create_contacts",
                        "download_ww_quote_payment_schedule",
                        "view_own_sales_orders",
                        "download_ww_quote_pdf",
                        "download_quote_schedule",
                        "view_addresses",
                        "view_countries",
                        "create_assets",
                        "view_opportunities",
                        "create_ww_quote_files",
                        "view_promo_discounts",
                        "create_opportunities",
                        "download_quote_price",
                        "view_own_ww_quote_files",
                        "create_sales_orders",
                        "download_contract_pdf",
                        "view_discounts",
                        "create_companies",
                        "download_sales_order_pdf",
                        "download_hpe_contract_pdf",
                        "view_contacts",
                        "update_own_ww_quotes",
                        "view_prepay_discounts",
                        "view_assets",
                        "create_ww_quotes",
                        "update_addresses",
                        "view_vendors",
                        "download_quote_pdf",
                        "download_ww_quote_distributor_file",
                        "create_countries",
                        "create_addresses",
                        "create_vendors",
                        "update_companies",
                        "update_own_ww_quote_files",
                        "update_countries",
                        "view_activities",
                        "view_multiyear_discounts",
                        "view_margins",
                        "handle_own_ww_quote_files",
                        "update_contacts",
                        "view_sn_discounts",
                        "update_vendors",
                        "update_own_sales_orders",
                    ])
                    ->get()
            )->state(['name' => 'WW Account Managers']))
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->forgetCachedPermissions();
        $user->roles->first()->forgetCachedPermissions();

        /** @var WorldwideQuote $quote */
        $quote = WorldwideQuote::factory()
            ->for(
                $quoteOwner = User::factory()
                    ->hasAttached($user->roles->first())
                    ->for($user->ledTeams->first(), 'team')
                    ->create()
                , 'user'
            )
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create();

        $this->assertTrue($user->can('update', $quote));
        $this->assertTrue($user->can('update', $quote->opportunity));
    }

    /**
     * Test an ability to partially update an existing opportunity.
     */
    public function testCanPartiallyUpdateOpportunity(): void
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $this->app['auth.driver']->id(),
        ]);

        $this->patchJson('api/opportunities/'.$opportunity->getKey(),
            $data = [
                'customer_order_date' => $this->faker->dateTimeBetween('+1day', '+3day')->format('Y-m-d'),
            ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/opportunities/'.$opportunity->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'customer_order_date',
            ]);

        foreach ($data as $attr => $value) {
            $this->assertSame($value, $response->json($attr));
        }
    }

    /**
     * Test an ability to update an existing opportunity with contract duration.
     */
    public function testCanUpdateOpportunityWithContractDuration(): void
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create([
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

            $contact = Contact::factory()->create();

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

        $data = Opportunity::factory()->raw([
            'primary_account_id' => $primaryAccountID,
            'primary_account_contact_id' => $primaryAccountContactID,
            'is_contract_duration_checked' => true,
            'contract_duration_months' => 60,
            'sale_action_name' => Pipeline::query()->where('is_default',
                1)->sole()->pipelineStages->random()->qualifiedStageName,
        ]);

        $data['suppliers_grid'] = collect()->times(5, function (int $n) {
            /** @var CustomField $field */
            $field = CustomField::query()->where('field_name', "opportunity_distributor$n")->sole();

            /** @var CustomFieldValue $value */
            $value = $field->values()->whereHas('allowedBy')->first();

            return [
                'supplier_name' => $value->field_value,
                'country_name' => $value->allowedBy->random()->field_value,
                'contact_name' => $this->faker->firstName(),
                'contact_email' => $this->faker->companyEmail(),
            ];
        })->all();

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
        $newContactOfPrimaryAccount = Contact::factory()->create();

        $this->patchJson('api/companies/'.$primaryAccountID, [
            'sales_unit_id' => SalesUnit::query()->get()->random()->getKey(),
            'name' => $account->name,
            'vat_type' => 'NO VAT',
            'type' => 'External',
            'source' => 'Pipeliner',
            'category' => 'Reseller',
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
    public function testCanDeleteOpportunity(): void
    {
        $this->authenticateApi();

        $opportunity = Opportunity::factory()->create([
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
     * The new fields added: Reseller(yes/no), End User(yes/no), Vendor(Lenovo, IBM).
     */
    public function testCanUploadOpportunitiesBy20220121(): void
    {
        $accountsDataFile = UploadedFile::fake()->createWithContent('accounts-0211.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/accounts-21012022.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('contacts-0211.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/contacts-21012022.xlsx')));

        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps-0211.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/opps-21012022.xlsx')));

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
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount',
                        'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date',
                        'sale_action_name', 'campaign_name',
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
                        '*' => [
                            'id', 'address_type', 'address_1', 'city', 'state', 'post_code', 'address_2',
                            'country_id',
                        ],
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

        $groupedAddresses = collect($response->json('primary_account.addresses'))
            ->map(static fn(array $a) => [
                'address_type' => $a['address_type'],
                'address_1' => $a['address_1'],
                'address_2' => $a['address_2'],
                'city' => $a['city'],
                'state' => $a['state'],
                'post_code' => $a['post_code'],
                'country_code' => Arr::get($a, 'country.iso_3166_2'),
            ])
            ->groupBy('address_type')
            ->toArray();

        $this->assertArrayHasKey('Invoice', $groupedAddresses);
        $this->assertCount(3, $groupedAddresses['Invoice']);

        $expectedAddresses = [
            [
                "address_type" => "Invoice",
                "address_1" => "Verseci u. 1-15",
                "address_2" => null,
                "city" => "Székesfehérvár,",
                "state" => null,
                "post_code" => "8000",
                "country_code" => "HU",
            ],
            [
                "address_type" => "Invoice",
                "address_1" => "Riverside One, 22 Hester Road",
                "address_2" => null,
                "city" => "London",
                "state" => "London",
                "post_code" => "SW11 4AN",
                "country_code" => "GB",
            ],
        ];

        foreach ($expectedAddresses as $expectedAddress) {
            $this->assertContainsEquals($expectedAddress, $groupedAddresses['Invoice']);
        }
    }

    /**
     * Test an ability to batch upload the opportunities from a file.
     * The new fields added to the accounts file: Address 2, Hardware Country Code, Hardware Country Code, VAT, VAT Type, State Code
     * The new fields added to the contacts file: Type(Hardware, Software)
     */
    public function testCanUploadOpportunitiesBy20220323(): void
    {
        $accountsDataFile = UploadedFile::fake()->createWithContent('accounts.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/accounts-23032022.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('contacts.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/contacts-23032022.xlsx')));

        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/opps-23032022.xlsx')));

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
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount',
                        'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date',
                        'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
            ]);

        $this->assertEmpty($response->json('errors'));

        $this->assertNotEmpty($response->json('opportunities'));

        $this->assertCount(1, $response->json('opportunities'));

        $this->patchJson('api/opportunities/save', [
            'opportunities' => $response->json('opportunities.*.id'),
        ])
//            ->dump()
            ->assertNoContent();

        $response = $this->getJson('api/opportunities/'.$response->json('opportunities.0.id'))
//            ->dump()
            ->assertJsonStructure([
                'id',
                'sales_unit' => [
                    'id',
                    'unit_name',
                ],
                'primary_account' => [
                    'id', 'name',
                    'vendors' => [
                        '*' => ['id', 'name', 'short_code',],
                    ],
                    'addresses' => [
                        '*' => [
                            'id', 'address_type', 'address_1', 'city', 'state', 'post_code', 'address_2',
                            'country_id',
                        ],
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

        $this->assertSame('Worldwide', $response->json('sales_unit.unit_name'));

        $this->assertSame('Foster and Partners', $response->json('primary_account.name'));
        $this->assertSame('Foster and Partners', $response->json('end_user.name'));
        $this->assertSame($response->json('primary_account.id'), $response->json('end_user.id'));

        $this->assertSame('Mehdi', $response->json('primary_account_contact.first_name'));
        $this->assertSame('DOUMBIA', $response->json('primary_account_contact.last_name'));

        $this->assertSame('1tFEnogc3tRACoEy', $response->json('primary_account.vat'));
        $this->assertSame('VAT Number', $response->json('primary_account.vat_type'));

        $addressOfPrimaryAccountByType = collect($response->json('primary_account.addresses'))
            ->groupBy('address_type')
            ->all();

        $this->assertArrayHasKey('Invoice', $addressOfPrimaryAccountByType);
        $this->assertArrayHasKey('Hardware', $addressOfPrimaryAccountByType);

        $hardwareAddress = $addressOfPrimaryAccountByType['Hardware'][0];
        $invoiceAddress = $addressOfPrimaryAccountByType['Invoice'][0];

        $this->assertSame('Hardware', $hardwareAddress['address_type']);
        $this->assertSame('Unit 5, Mole Business Park', $hardwareAddress['address_1']);
        $this->assertSame('Leatherhead', $hardwareAddress['city']);
        $this->assertSame('Surrey', $hardwareAddress['state']);
        $this->assertNull($hardwareAddress['state_code']);
        $this->assertSame('KT22 7BA', $hardwareAddress['post_code']);
        $this->assertSame('Randalls Road', $hardwareAddress['address_2']);
        $this->assertSame('GB', $hardwareAddress['country']['iso_3166_2']);

        $this->assertSame('Invoice', $invoiceAddress['address_type']);
//        $this->assertSame('b5cohmuDjaXXQLAB', $invoiceAddress['address_1']);
//        $this->assertSame('q7MP5mllTHffSyGZ', $invoiceAddress['address_2']);
//        $this->assertSame('9lGBq3bflmjwClUz', $invoiceAddress['city']);
//        $this->assertSame('sNhUs3kLbEMYZVvZ', $invoiceAddress['post_code']);
//        $this->assertSame('5rne8e6cCnsF834v', $invoiceAddress['state_code']);
//        $this->assertSame('wFohxABSHPIWQyC3', $invoiceAddress['state']);
//        $this->assertSame('GB', $invoiceAddress['country']['iso_3166_2']);
    }

    /**
     * Test an ability to batch upload the opportunities from a file without accounts & contacts file.
     * The new fields added: Reseller(yes/no), End User(yes/no), Vendor(Lenovo, IBM).
     */
    public function testCanUploadOpportunitiesBy20220121WithoutAccountsAndContactsFile(): void
    {
        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps-0211.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/opps-21012022.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount',
                        'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date',
                        'sale_action_name', 'campaign_name',
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
        $existingCompany = Company::factory()->create([
            'name' => 'AT Company 5',
            'type' => 'External',
        ]);

        $opportunitiesFile = UploadedFile::fake()->createWithContent('opps-0211.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/opps-25012022.xlsx')));

        $this->authenticateApi();

        $response = $this->postJson('api/opportunities/upload', [
            'opportunities_file' => $opportunitiesFile,
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'opportunities' => [
                    '*' => [
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount',
                        'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date',
                        'sale_action_name', 'campaign_name',
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
                        '*' => [
                            'id', 'address_type', 'address_1', 'city', 'state', 'post_code', 'address_2',
                            'country_id',
                        ],
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
                    ],
                ],
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
    public function testCanBatchUploadOpportunitiesWithoutAccountsDataFiles(): void
    {
        $opportunitiesFile = UploadedFile::fake()->createWithContent('Opportunities-04042021.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/Opportunities-04042021.xlsx')));

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
                        'id', 'opportunity_type', 'account_name', 'account_manager_name', 'opportunity_amount',
                        'opportunity_start_date', 'opportunity_end_date', 'opportunity_closing_date',
                        'sale_action_name', 'campaign_name',
                    ],
                ],
                'errors',
            ]);

        $this->assertNotEmpty($response->json('errors'));
    }

    /**
     * Test an ability to see matched own primary account in imported opportunity,
     * when multiple companies are present with the same name owned by different users.
     *
     */
    public function testCanSeeMatchedOwnPrimaryAccountInImportedOpportunity(): void
    {
        $opportunitiesFile = UploadedFile::fake()->createWithContent('ops-17012022.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/ops-17012022.xlsx')));

        $accountsDataFile = UploadedFile::fake()->createWithContent('accounts-17012022.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/accounts-17012022.xlsx')));

        $accountContactsFile = UploadedFile::fake()->createWithContent('contacts-17012022.xlsx',
            file_get_contents(base_path('tests/Feature/Data/opportunity/contacts-17012022.xlsx')));

        $otherUserCompany = Company::factory()->create([
            'user_id' => User::factory()->create()->getKey(),
            'name' => 'ASA Computer',
            'type' => 'External',
        ]);

        $this->authenticateApi();

        $ownCompany = Company::factory()->create([
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
    public function testCanMarkOpportunityAsLost(): void
    {
        $opportunity = Opportunity::factory()->create([
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
    public function testCanRestoreOpportunity(): void
    {
        $opportunity = Opportunity::factory()->create([
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
    public function testCanNotDeleteOpportunityWithAttachedQuote(): void
    {
        $opportunity = Opportunity::factory()->create();

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
     */
    public function testCanViewOpportunitiesGroupedByPipelineStages(): void
    {
        $this->authenticateApi();

        $pipelineStagesResp = $this->getJson('api/pipelines/default')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'space_id',
                'space_name',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'stage_name',
                        'stage_order',
                        'stage_percentage',
                        'qualified_stage_name',
                    ],
                ],
            ]);

        $this->assertNotEmpty($pipelineStagesResp->json('pipeline_stages'));

        $oppsWithQuote = [];

        foreach ($pipelineStagesResp->json('pipeline_stages') as $stage) {
            Opportunity::factory()
                ->has(OpportunityValidationResult::factory(), relationship: 'validationResult')
                ->count(2)->create([
                    'pipeline_id' => $pipelineStagesResp->json('id'),
                    'pipeline_stage_id' => $stage['id'],
                    'sale_action_name' => $stage['qualified_stage_name'],
//                'primary_account_id' => Company::factory()->create()->getKey(),
//                'primary_account_contact_id' => Contact::factory()->create()->getKey(),
                ]);

            $oppsWithQuote[] = $oppWithQuote = Opportunity::factory()->create([
                'pipeline_id' => $pipelineStagesResp->json('id'),
                'pipeline_stage_id' => $stage['id'],
                'sale_action_name' => $stage['qualified_stage_name'],
            ]);

            factory(WorldwideQuote::class)->create([
                'opportunity_id' => $oppWithQuote->getKey(),
            ]);
        }

        $response = $this->getJson('api/pipelines/default/stage-opportunity')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'stage_id',
                    'stage_name',
                    'stage_order',
                    'stage_percentage',
                    'summary' => [
                        'total',
                        'valid',
                        'invalid',
                        'base_amount',
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                    ],
                    'opportunities' => [
                        '*' => [
                            'id',
                            'user_id',
                            'pipeline_stage_id',
                            'account_manager_id',
                            'project_name',
                            'opportunity_closing_date',
                            'opportunity_amount',
                            'base_opportunity_amount',
                            'opportunity_amount_currency_code',

                            'account_manager' => [
                                'id', 'email', 'user_fullname',
                            ],

                            'primary_account' => [
                                'id', 'name', 'phone', 'email', 'logo',
                            ],

                            'end_user' => [
                                'id', 'name', 'phone', 'email', 'logo',
                            ],

                            'primary_account_contact' => [
                                'id', 'first_name', 'last_name', 'phone', 'email',
                            ],

                            'validation_result' => [
                                'messages', 'is_passed',
                            ],

                            'opportunity_start_date',
                            'opportunity_end_date',
                            'ranking',
                            'status',
                            'status_reason',
                            'created_at',

                            'permissions' => [
                                'view',
                                'update',
                                'delete',
                            ],
                        ],
                    ],
                ],
            ]);

        foreach ($response->json() as $stage) {
            $this->assertNotEmpty($stage['opportunities']);
        }


        // Assert pipeline view contain opportunities with quotes.
        $opportunityKeys = $response->json('*.opportunities.*.id');

        foreach ($oppsWithQuote as $opp) {
            $this->assertContains($opp->getKey(), $opportunityKeys);
        }
    }

    /**
     * Test an ability to view own opportunity entities grouped by pipeline stages.
     *
     */
    public function testCanViewOwnOpportunitiesGroupedByPipelineStages(): void
    {
        $anotherUser = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        /** @var User $currentUser */
        $currentUser = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        /** @var Role $role */
        $role = factory(Role::class)->create();
        $role->syncPermissions('view_opportunities');

        $currentUser->syncRoles($role);

        $this->authenticateApi($currentUser);

        $pipelineStagesResp = $this->getJson('api/pipelines/default')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'space_id',
                'space_name',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'stage_name',
                        'stage_order',
                        'stage_percentage',
                        'qualified_stage_name',
                    ],
                ],
            ]);

        $this->assertNotEmpty($pipelineStagesResp->json('pipeline_stages'));

        $currentUserOpportunities = [];

        $pipeline = Pipeline::query()->findOrFail($pipelineStagesResp->json('id'));

        foreach ($pipelineStagesResp->json('pipeline_stages') as $stage) {
            $pipelineStage = $pipeline->pipelineStages->find($stage['id']);

            Opportunity::factory()
                ->for($anotherUser)
                ->for($pipeline)
                ->for($pipelineStage)
                ->for(SalesUnit::factory())
                ->create();

            $currentUserOpportunities[$stage['id']] ??= [];
            $currentUserOpportunities[$stage['id']][] = Opportunity::factory()
                ->for($currentUser)
                ->for($pipeline)
                ->for($pipelineStage)
                ->for($currentUser->salesUnits->first())
                ->create();
        }

        $response = $this->getJson('api/pipelines/default/stage-opportunity')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'stage_id',
                    'stage_name',
                    'stage_order',
                    'stage_percentage',
                    'summary' => [
                        'total',
                        'valid',
                        'invalid',
                        'base_amount',
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                    ],
                    'opportunities' => [
                        '*' => [
                            'id',
                            'user_id',
                            'pipeline_stage_id',
                            'account_manager_id',
                            'project_name',
                            'opportunity_closing_date',
                            'opportunity_amount',
                            'base_opportunity_amount',
                            'opportunity_amount_currency_code',

                            'account_manager' => [
                                'id', 'email', 'user_fullname',
                            ],

                            'primary_account' => [
                                'id', 'name', 'phone', 'email', 'logo',
                            ],

                            'end_user' => [
                                'id', 'name', 'phone', 'email', 'logo',
                            ],

                            'primary_account_contact' => [
                                'id', 'first_name', 'last_name', 'phone', 'email',
                            ],

                            'opportunity_start_date',
                            'opportunity_end_date',
                            'ranking',
                            'status',
                            'status_reason',
                            'created_at',

                            'permissions' => [
                                'view',
                                'update',
                                'delete',
                            ],
                        ],
                    ],
                ],
            ]);

        foreach ($response->json() as $stage) {
            $opportunitiesOfStage = $currentUserOpportunities[$stage['stage_id']];

            $this->assertCount(count($opportunitiesOfStage), $stage['opportunities']);

            foreach ($opportunitiesOfStage as $opportunity) {
                $this->assertContains($opportunity->getKey(), array_column($stage['opportunities'], 'id'));
            }
        }
    }

    /**
     * Test an ability to move opportunity between pipeline stages.
     */
    public function testCanMoveOpportunityBetweenStages(): void
    {
        $this->authenticateApi();

        Opportunity::query()->delete();

        $pipelineStagesResp = $this->getJson('api/pipelines/default')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'space_id',
                'space_name',
                'pipeline_name',
                'pipeline_stages' => [
                    '*' => [
                        'id',
                        'stage_name',
                        'stage_order',
                        'stage_percentage',
                        'qualified_stage_name',
                    ],
                ],
            ]);

        $this->assertNotEmpty($pipelineStagesResp->json('pipeline_stages'));
        $this->assertTrue(count($pipelineStagesResp->json('pipeline_stages')) > 1);

        $opp = Opportunity::factory()->create([
            'order_in_pipeline_stage' => 0,
            'pipeline_id' => $pipelineStagesResp->json('id'),
            'pipeline_stage_id' => $pipelineStagesResp->json('pipeline_stages.0.id'),
            'sale_action_name' => $pipelineStagesResp->json('pipeline_stages.0.qualified_stage_name'),
        ]);

        $opp2 = Opportunity::factory()->create([
            'order_in_pipeline_stage' => 0,
            'pipeline_id' => $pipelineStagesResp->json('id'),
            'pipeline_stage_id' => $pipelineStagesResp->json('pipeline_stages.1.id'),
            'sale_action_name' => $pipelineStagesResp->json('pipeline_stages.1.qualified_stage_name'),
        ]);

        $opp3 = Opportunity::factory()->create([
            'order_in_pipeline_stage' => 3,
            'pipeline_id' => $pipelineStagesResp->json('id'),
            'pipeline_stage_id' => $pipelineStagesResp->json('pipeline_stages.1.id'),
            'sale_action_name' => $pipelineStagesResp->json('pipeline_stages.1.qualified_stage_name'),
        ]);

        $opp4 = Opportunity::factory()->create([
            'order_in_pipeline_stage' => 6,
            'pipeline_id' => $pipelineStagesResp->json('id'),
            'pipeline_stage_id' => $pipelineStagesResp->json('pipeline_stages.1.id'),
            'sale_action_name' => $pipelineStagesResp->json('pipeline_stages.1.qualified_stage_name'),
        ]);

        $pipelineResp = $this->getJson('api/pipelines/default/stage-opportunity')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'stage_id',
                    'stage_name',
                    'stage_order',
                    'stage_percentage',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                    ],
                    'summary' => [
                        'total',
                        'valid',
                        'invalid',
                        'base_amount',
                    ],
                    'opportunities' => [
                        '*' => [
                            'id',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($pipelineResp->json('0.opportunities'));
        $this->assertSame($opp->getKey(), $pipelineResp->json('0.opportunities.0.id'));

        $this->patchJson("api/opportunities/{$opp->getKey()}/stage", [
            'order_in_stage' => 1,
            'stage_id' => $pipelineStagesResp->json('pipeline_stages.1.id'),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'stage_id',
                        'total',
                        'valid',
                        'invalid',
                        'base_amount',
                    ],
                ],
            ]);

        $this->getJson("api/opportunities/{$opp->getKey()}")
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'sale_action_name',
                'order_in_pipeline_stage',
            ])
            ->assertJson([
                'order_in_pipeline_stage' => 1,
                'sale_action_name' => $pipelineStagesResp->json('pipeline_stages.1.qualified_stage_name'),
            ]);

        $pipelineResp = $this->getJson('api/pipelines/default/stage-opportunity')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'stage_id',
                    'stage_name',
                    'stage_order',
                    'stage_percentage',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                    ],
                    'summary' => [
                        'total',
                        'valid',
                        'invalid',
                        'base_amount',
                    ],
                    'opportunities' => [
                        '*' => [
                            'id',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($pipelineResp->json('1.opportunities'));
        $this->assertSame($opp->getKey(), $pipelineResp->json('1.opportunities.1.id'));
    }
}
