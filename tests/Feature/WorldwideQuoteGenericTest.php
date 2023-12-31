<?php

namespace Tests\Feature;

use App\Domain\Address\Enum\AddressType;
use App\Domain\Address\Models\Address;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\OpportunitySupplier;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group worldwide
 * @group worldwide-quote
 * @group build
 */
class WorldwideQuoteGenericTest extends TestCase
{
    /**
     * Test an ability to view merged addresses of worldwide quote.
     */
    public function testCanViewMergedAddressesOfWorldwideQuote(): void
    {
        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::factory()->create();

        $opportunity->primaryAccount->addresses()->sync(factory(Address::class, 2)->create());
        $opportunity->endUser->addresses()->sync(factory(Address::class, 2)->create());

        $quote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $this->authenticateApi();

        $response = $this->getJson("api/ww-quotes/{$quote->getKey()}?".Arr::query([
                'include' => [
                    'opportunity.primaryAccount.addresses',
                    'opportunity.endUser.addresses',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'opportunity' => [
                    'merged_addresses' => [
                        '*' => ['id', 'address_string'],
                    ],
                ],
            ]);

        $this->assertCount($opportunity->primaryAccount->addresses->count() + $opportunity->endUser->addresses->count(),
            $response->json('opportunity.merged_addresses'));
    }

    /**
     * Test an ability to view merged contacts of worldwide quote.
     */
    public function testCanViewMergedContactsOfWorldwideQuote(): void
    {
        /** @var Opportunity $opportunity */
        $opportunity = Opportunity::factory()->create();

        $opportunity->primaryAccount->addresses()->sync(factory(Address::class, 2)->create());
        $opportunity->endUser->addresses()->sync(factory(Address::class, 2)->create());

        $quote = factory(WorldwideQuote::class)->create([
            'opportunity_id' => $opportunity->getKey(),
        ]);

        $this->authenticateApi();

        $response = $this->getJson("api/ww-quotes/{$quote->getKey()}?".Arr::query([
                'include' => [
                    'opportunity.primaryAccount.contacts',
                    'opportunity.endUser.contacts',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'opportunity' => [
                    'merged_contacts' => [
                        '*' => ['id'],
                    ],
                ],
            ]);

        $this->assertCount($opportunity->primaryAccount->contacts->count() + $opportunity->endUser->contacts->count(),
            $response->json('opportunity.merged_contacts'));
    }

    /**
     * Test an ability to view paginated worldwide quotes.
     */
    public function testCanViewPaginatedWorldwideQuotes(): void
    {
        $this->authenticateApi();

        foreach (range(1, 10) as $time) {
            factory(WorldwideQuote::class)->create();

            $opportunity = Opportunity::factory()->create();
            $opportunitySupplier = factory(OpportunitySupplier::class)->create(['opportunity_id' => $opportunity->getKey()]);
            $submittedQuote = factory(WorldwideQuote::class)->create([
                'submitted_at' => now(), 'opportunity_id' => $opportunity->getKey(),
            ]);

            $quoteFile = factory(QuoteFile::class)->create();

            factory(WorldwideDistribution::class)->create([
                'worldwide_quote_id' => $submittedQuote->getKey(),
                'worldwide_quote_type' => WorldwideQuote::class,
                'opportunity_supplier_id' => $opportunitySupplier->getKey(),
                'distributor_file_id' => $quoteFile->getKey(),
                'schedule_file_id' => $quoteFile->getKey(),
            ]);

            factory(SalesOrder::class)->create();
            factory(SalesOrder::class)->create(['submitted_at' => now()]);
        }

        $this->getJson('api/ww-quotes/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_fullname',
                        'sharing_users' => [
                            '*' => [
                                'id',
                                'email',
                                'first_name',
                                'middle_name',
                                'last_name',
                                'user_fullname',
                                'picture',
                            ],
                        ],
                        'opportunity_id',
                        'company_id',
                        'type_name',
                        'completeness',
                        'stage',
                        'created_at',
                        'updated_at',
                        'activated_at',
                        'company_name',
                        'customer_name',
                        'end_user_name',
                        'rfq_number',
                        'valid_until_date',
                        'customer_support_start_date',
                        'customer_support_end_date',

                        'active_version_id',

                        'versions' => [
                            '*' => [
                                'id',
                                'worldwide_quote_id',
                                'user_id',
                                'user_fullname',
                                'user_version_sequence_number',
                                'updated_at',
                                'version_name',
                                'is_active_version',
                            ],
                        ],

                        'status',
                        'status_reason',

                        'permissions' => [
                            'view', 'update', 'delete', 'change_status',
                        ],

                        'created_at',
                        'updated_at',
                        'activated_at',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'next',
                    'prev',
                ],

                // TODO: remove
                'from',
                'path',
                'per_page',
                'last_page',
                'to',
                'total',

                'meta' => [
                    'last_page',
                    'from',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $this->getJson('api/ww-quotes/drafted?search=1234')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_customer_name=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_end_user_name=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_completeness=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_user_fullname=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_rfq_number=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_valid_until_date=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_support_start_date=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_support_end_date=asc')->assertOk();
        $this->getJson('api/ww-quotes/drafted?order_by_created_at=asc')->assertOk();

        $this->getJson('api/ww-quotes/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_fullname',
                        'sharing_users' => [
                            '*' => [
                                'id',
                                'email',
                                'first_name',
                                'middle_name',
                                'last_name',
                                'user_fullname',
                                'picture',
                            ],
                        ],
                        'sales_order_id',
                        'has_sales_order',
                        'sales_order_submitted',
                        'has_distributor_files',
                        'has_schedule_files',
                        'opportunity_id',
                        'type_name',
                        'company_id',
                        'completeness',
                        'created_at',
                        'updated_at',
                        'activated_at',
                        'company_name',
                        'primary_account_id',
                        'customer_name',
                        'end_user_id',
                        'end_user_name',
                        'rfq_number',
                        'valid_until_date',
                        'customer_support_start_date',
                        'customer_support_end_date',

                        'status',
                        'status_reason',

                        'permissions' => [
                            'view', 'update', 'delete', 'change_status',
                        ],
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'next',
                    'prev',
                ],

                // TODO: remove
                'from',
                'path',
                'per_page',
                'last_page',
                'to',
                'total',

                'meta' => [
                    'last_page',
                    'from',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $this->getJson('api/ww-quotes/submitted?search=1234')->assertOk();
        $this->getJson('api/ww-quotes/submitted?order_by_customer_name=asc')->assertOk();
        $this->getJson('api/ww-quotes/submitted?order_by_end_user_name=asc')->assertOk();
        $this->getJson('api/ww-quotes/submitted?order_by_rfq_number=asc')->assertOk();
        $this->getJson('api/ww-quotes/submitted?order_by_valid_until_date=asc')->assertOk();
        $this->getJson('api/ww-quotes/submitted?order_by_support_start_date=asc')->assertOk();
        $this->getJson('api/ww-quotes/submitted?order_by_support_end_date=asc')->assertOk();
        $this->getJson('api/ww-quotes/submitted?order_by_created_at=asc')->assertOk();
    }

    /**
     * Test an ability to view own paginated drafted worldwide quotes.
     */
    public function testCanViewOwnPaginatedDraftedWorldwideQuotes(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_own_ww_quotes');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        // Own WorldwideQuote entity.
        $quote = WorldwideQuote::factory()
            ->for($user)
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => null,
            ]);

        // WorldwideQuote entity own by another user.
        WorldwideQuote::factory()
            ->for($anotherUser = User::factory()->create())
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => null,
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/ww-quotes/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));

        foreach ($response->json('data.*.user_id') as $ownerUserKey) {
            $this->assertSame($user->getKey(), $ownerUserKey);
        }
    }

    /**
     * Test an ability to view paginated drafted worldwide quotes as editor.
     */
    public function testCanViewPaginatedDraftedWorldwideQuotesAsEditor(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_own_ww_quotes', 'view_ww_quotes_where_editor');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        // Worldwide quote which has sharing user relation to the current user.
        $quote = WorldwideQuote::factory()
            ->hasAttached($user, relationship: 'sharingUsers')
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => null,
            ]);

        // WorldwideQuote entity own by another user.
        WorldwideQuote::factory()
            ->for($anotherUser = User::factory()->create())
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => null,
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/ww-quotes/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $quote->getKey());

        $this->assertNotSame($response->json('data.0.user_id'), $user->getKey());
    }

    /**
     * Test an ability to view own paginated submitted worldwide quotes.
     */
    public function testCanViewOwnPaginatedSubmittedWorldwideQuotes(): void
    {
        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = Role::factory()->create();

        $role->syncPermissions('view_own_ww_quotes');

        /** @var \App\Domain\User\Models\User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        // Own WorldwideQuote entity.
        $quote = WorldwideQuote::factory()
            ->for($user)
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => now(),
            ]);

        // WorldwideQuote entity own by another user.
        WorldwideQuote::factory()
            ->for($anotherUser = User::factory()->create())
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => now(),
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/ww-quotes/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));

        foreach ($response->json('data.*.user_id') as $ownerUserKey) {
            $this->assertSame($user->getKey(), $ownerUserKey);
        }
    }

    /**
     * Test an ability to view paginated submitted worldwide quotes as editor.
     */
    public function testCanViewPaginatedSubmittedWorldwideQuotesAsEditor(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_own_ww_quotes', 'view_ww_quotes_where_editor');

        /** @var User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        // Worldwide quote which has sharing user relation to the current user.
        $quote = WorldwideQuote::factory()
            ->hasAttached($user, relationship: 'sharingUsers')
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => now(),
            ]);

        // WorldwideQuote entity own by another user.
        WorldwideQuote::factory()
            ->for($anotherUser = User::factory()->create())
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => now(),
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/ww-quotes/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $quote->getKey());

        $this->assertNotSame($response->json('data.0.user_id'), $user->getKey());
    }

    /**
     * Test an ability to view only drafted quotes belong to assigned sales units to user.
     */
    public function testCanViewDraftedQuotesBelongToAssignedSalesUnitsToUser(): void
    {
        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_own_ww_quotes');

        /** @var \App\Domain\User\Models\User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        // Own WorldwideQuote entity.
        $quote = WorldwideQuote::factory()
            ->for($user)
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => null,
            ]);

        // Owned but belong to different sales unit.
        WorldwideQuote::factory()
            ->for($user)
            ->for(
                Opportunity::factory()
                    ->for(SalesUnit::factory())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => null,
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/ww-quotes/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'sales_unit_id',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));

        foreach ($response->json('data') as $quote) {
            $this->assertSame($user->getKey(), $quote['user_id']);
            $this->assertContains($quote['sales_unit_id'], $user->salesUnits->modelKeys());
        }
    }

    /**
     * Test an ability to view only submitted quotes belong to assigned sales units to user.
     */
    public function testCanViewSubmittedQuotesBelongToAssignedSalesUnitsToUser(): void
    {
        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions('view_own_ww_quotes');

        /** @var \App\Domain\User\Models\User $user */
        $user = User::factory()
            ->hasAttached(SalesUnit::factory())
            ->create();

        $user->syncRoles($role);

        // Own WorldwideQuote entity.
        $quote = WorldwideQuote::factory()
            ->for($user)
            ->for(
                Opportunity::factory()
                    ->for($user->salesUnits->first())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => now(),
            ]);

        // Owned but belong to different sales unit.
        WorldwideQuote::factory()
            ->for($user)
            ->for(
                Opportunity::factory()
                    ->for(SalesUnit::factory())
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
                'submitted_at' => now(),
            ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('api/ww-quotes/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'sales_unit_id',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));

        foreach ($response->json('data') as $quote) {
            $this->assertSame($user->getKey(), $quote['user_id']);
            $this->assertContains($quote['sales_unit_id'], $user->salesUnits->modelKeys());
        }
    }

    public function testItPreservesPrimaryAccountInvoiceAddressOnSubmission(): void
    {
        /** @var Address $invoiceAddress */
        $invoiceAddress = Address::factory([
            'address_type' => AddressType::INVOICE,
            'address_1' => Str::random(40),
            'address_2' => Str::random(40),
        ])->create();

        /** @var \App\Domain\Worldwide\Models\WorldwideQuote $wwQuote */
        $wwQuote = WorldwideQuote::factory()
            ->for(
                Opportunity::factory()
                    ->for(
                        Company::factory()->hasAttached(
                            $invoiceAddress,
                            pivot: ['is_default' => true]
                        ),
                        relationship: 'primaryAccount'
                    )
            )
            ->create([
                'contract_type_id' => CT_CONTRACT,
            ]);

        $wwQuote->activeVersion->quoteTemplate()->associate(QuoteTemplate::query()->first());
        $wwQuote->activeVersion->save();

        $this->authenticateApi();

        $this->postJson('api/ww-quotes/'.$wwQuote->getKey().'/submit', [
            'quote_closing_date' => now()->addDays(10)->toDateString(),
            'additional_notes' => 'Note for submitted quote',
        ])
//            ->dump()
            ->assertNoContent();

        $wwQuote->opportunity->primaryAccount->addresses()->detach();

        $this->getJson('api/ww-quotes/'.$wwQuote->getKey().'/preview')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'quote_summary' => [
                    'primary_account_inv_address_1',
                    'primary_account_inv_address_2',
                    'primary_account_inv_country',
                    'primary_account_inv_state',
                    'primary_account_inv_state_code',
                    'primary_account_inv_post_code',
                ],
            ])
            ->assertJsonPath('quote_summary.primary_account_inv_address_1', $invoiceAddress->address_1);
    }
}
