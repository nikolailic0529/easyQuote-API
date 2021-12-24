<?php

namespace Tests\Feature;

use App\Models\Quote\Quote;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Tests\TestCase;

class UnifiedQuoteTest extends TestCase
{
    /**
     * Test an ability to view paginated unified expiring quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedUnifiedExpiringQuotes()
    {
        $this->authenticateApi();

        $this->getJson('api/unified-quotes/expiring')
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
                        'valid_until_date',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->getJson('api/unified-quotes/expiring?order_by_updated_at=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_rfq_number=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_company_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_customer_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/expiring?order_by_completeness=desc')
//            ->dump()
            ->assertOk();

    }

    /**
     * Test an ability to view only own unified expiring quotes.
     *
     * @return void
     */
    public function testCanViewOnlyOwnUnifiedExpiringQuotes()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_own_quote_contracts',
            'view_own_quotes',
            'download_sales_order_pdf',
            'update_own_external_quotes',
            'update_own_internal_quotes',
            'create_external_quotes',
            'create_contracts',
            'view_own_internal_quotes',
            'delete_own_quote_contracts',
            'delete_own_external_quotes',
            'download_hpe_contract_pdf',
            'download_ww_quote_payment_schedule',
            'view_own_hpe_contracts',
            'download_ww_quote_pdf',
            'download_quote_schedule',
            'create_quote_files',
            'cancel_sales_orders',
            'download_quote_price',
            'download_quote_pdf',
            'delete_quote_files',
            'delete_own_internal_quotes',
            'update_quote_files',
            'handle_quote_files',
            'create_internal_quotes',
            'update_own_quotes',
            'download_ww_quote_distributor_file',
            'download_contract_pdf',
            'view_own_contracts',
            'create_quote_contracts',
            'create_quotes',
            'view_own_external_quotes',
            'update_own_quote_contracts',
            'update_own_contracts',
            'delete_own_contracts',
            'update_own_hpe_contracts',
            'create_hpe_contracts',
            'delete_own_quotes',
            'view_quote_files',
            'delete_own_hpe_contracts']);

        /** @var User $user */
        $user = factory(User::class)->create();

        $user->syncRoles($role);

        $this->actingAs($user, 'api');

        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'user_id' => $user->getKey(),
            'submitted_at' => null,
        ]);

        $quote->customer->update([
            'valid_until' => now()->subDay()
        ]);

        $response = $this->getJson('api/unified-quotes/expiring')
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
                        'valid_until_date',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('data.*.user_id', [$user->getKey()]);
    }

    /**
     * Test an ability to view own and team led users own paginated expiring quotes.
     *
     * @return void
     */
    public function testCanViewOwnAndTeamLedUsersOwnPaginatedExpiringQuotes()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_own_quotes',
            'update_own_external_quotes',
            'update_own_internal_quotes'
        ]);

        /** @var User $teamLeader */
        $teamLeader = factory(User::class)->create();

        /** @var Team $team */
        $team = factory(Team::class)->create();

        $team->teamLeaders()->attach($teamLeader);

        $ledUser = factory(User::class)->create([
            'team_id' => $team->getKey()
        ]);

        $teamLeader->syncRoles($role);

        /** @var Quote $ownQuote */
        $ownQuote = factory(Quote::class)->create([
            'user_id' => $teamLeader->getKey(),
            'submitted_at' => null,
        ]);

        $ownQuote->customer->update([
            'valid_until' => today()->subDay()
        ]);

        /** @var Quote $ledUserOwnQuote */
        $ledUserOwnQuote = factory(Quote::class)->create([
            'user_id' => $ledUser->getKey(),
            'submitted_at' => null,
        ]);

        $ledUserOwnQuote->customer->update([
            'valid_until' => today()->subDay()
        ]);

        $this->actingAs($teamLeader, 'api');

        $response = $this->getJson('api/unified-quotes/expiring')
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
                        'valid_until_date',
                        'updated_at',
                        'activated_at',
                        'is_active',

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('data'));

        $this->assertContainsEquals($ownQuote->getKey(), $response->json('data.*.id'));
        $this->assertContainsEquals($ledUserOwnQuote->getKey(), $response->json('data.*.id'));
        $this->assertContainsEquals($teamLeader->getKey(), $response->json('data.*.user_id'));
        $this->assertContainsEquals($ledUser->getKey(), $response->json('data.*.user_id'));
    }

    /**
     * Test an ability to view paginated unified submitted quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedUnifiedSubmittedQuotes()
    {
        $this->authenticateApi();

        $this->getJson('api/unified-quotes/submitted')
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

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->getJson('api/unified-quotes/submitted?order_by_updated_at=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_rfq_number=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_company_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_customer_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/submitted?order_by_completeness=desc')
//            ->dump()
            ->assertOk();

    }

    /**
     * Test an ability to view only own unified submitted quotes.
     *
     * @return void
     */
    public function testCanViewOwnPaginatedUnifiedSubmittedQuotes()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_own_quote_contracts',
            'view_own_quotes',
            'download_sales_order_pdf',
            'update_own_external_quotes',
            'update_own_internal_quotes',
            'create_external_quotes',
            'create_contracts',
            'view_own_internal_quotes',
            'delete_own_quote_contracts',
            'delete_own_external_quotes',
            'download_hpe_contract_pdf',
            'download_ww_quote_payment_schedule',
            'view_own_hpe_contracts',
            'download_ww_quote_pdf',
            'download_quote_schedule',
            'create_quote_files',
            'cancel_sales_orders',
            'download_quote_price',
            'download_quote_pdf',
            'delete_quote_files',
            'delete_own_internal_quotes',
            'update_quote_files',
            'handle_quote_files',
            'create_internal_quotes',
            'update_own_quotes',
            'download_ww_quote_distributor_file',
            'download_contract_pdf',
            'view_own_contracts',
            'create_quote_contracts',
            'create_quotes',
            'view_own_external_quotes',
            'update_own_quote_contracts',
            'update_own_contracts',
            'delete_own_contracts',
            'update_own_hpe_contracts',
            'create_hpe_contracts',
            'delete_own_quotes',
            'view_quote_files',
            'delete_own_hpe_contracts']);

        /** @var User $user */
        $user = factory(User::class)->create();

        $user->syncRoles($role);

        $this->actingAs($user, 'api');

        factory(Quote::class)->create([
            'user_id' => $user->getKey(),
            'submitted_at' => now()
        ]);

        $response = $this->getJson('api/unified-quotes/submitted')
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

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('data.*.user_id', [$user->getKey()]);
    }

    /**
     * Test an ability to view own and team led users own paginated submitted quotes.
     *
     * @return void
     */
    public function testCanViewOwnAndTeamLedUsersOwnPaginatedUnifiedSubmittedQuotes()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_own_quotes',
            'update_own_external_quotes',
            'update_own_internal_quotes'
        ]);

        /** @var User $teamLeader */
        $teamLeader = factory(User::class)->create();

        /** @var Team $team */
        $team = factory(Team::class)->create();

        $team->teamLeaders()->attach($teamLeader);

        $ledUser = factory(User::class)->create([
            'team_id' => $team->getKey()
        ]);

        $teamLeader->syncRoles($role);

        /** @var Quote $ownQuote */
        $ownQuote = factory(Quote::class)->create([
            'user_id' => $teamLeader->getKey(),
            'submitted_at' => now(),
        ]);

        /** @var Quote $ledUserOwnQuote */
        $ledUserOwnQuote = factory(Quote::class)->create([
            'user_id' => $ledUser->getKey(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($teamLeader, 'api');

        $response = $this->getJson('api/unified-quotes/submitted')
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

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('data'));

        $this->assertContainsEquals($ownQuote->getKey(), $response->json('data.*.id'));
        $this->assertContainsEquals($ledUserOwnQuote->getKey(), $response->json('data.*.id'));
        $this->assertContainsEquals($teamLeader->getKey(), $response->json('data.*.user_id'));
        $this->assertContainsEquals($ledUser->getKey(), $response->json('data.*.user_id'));
    }

    /**
     * Test an ability to view paginated unified submitted quotes.
     *
     * @return void
     */
    public function testCanViewPaginatedUnifiedDraftedQuotes()
    {
        $this->authenticateApi();

        $this->getJson('api/unified-quotes/drafted')
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

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->getJson('api/unified-quotes/drafted?order_by_updated_at=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_rfq_number=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_company_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_customer_name=desc')
//            ->dump()
            ->assertOk();

        $this->getJson('api/unified-quotes/drafted?order_by_completeness=desc')
//            ->dump()
            ->assertOk();

    }

    /**
     * Test an ability to view only own unified drafted quotes.
     *
     * @return void
     */
    public function testCanViewOwnPaginatedUnifiedDraftedQuotes()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_own_quote_contracts',
            'view_own_quotes',
            'download_sales_order_pdf',
            'update_own_external_quotes',
            'update_own_internal_quotes',
            'create_external_quotes',
            'create_contracts',
            'view_own_internal_quotes',
            'delete_own_quote_contracts',
            'delete_own_external_quotes',
            'download_hpe_contract_pdf',
            'download_ww_quote_payment_schedule',
            'view_own_hpe_contracts',
            'download_ww_quote_pdf',
            'download_quote_schedule',
            'create_quote_files',
            'cancel_sales_orders',
            'download_quote_price',
            'download_quote_pdf',
            'delete_quote_files',
            'delete_own_internal_quotes',
            'update_quote_files',
            'handle_quote_files',
            'create_internal_quotes',
            'update_own_quotes',
            'download_ww_quote_distributor_file',
            'download_contract_pdf',
            'view_own_contracts',
            'create_quote_contracts',
            'create_quotes',
            'view_own_external_quotes',
            'update_own_quote_contracts',
            'update_own_contracts',
            'delete_own_contracts',
            'update_own_hpe_contracts',
            'create_hpe_contracts',
            'delete_own_quotes',
            'view_quote_files',
            'delete_own_hpe_contracts']);

        /** @var User $user */
        $user = factory(User::class)->create();

        $user->syncRoles($role);

        $this->actingAs($user, 'api');

        factory(Quote::class)->create([
            'user_id' => $user->getKey(),
            'submitted_at' => null
        ]);

        $response = $this->getJson('api/unified-quotes/drafted')
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

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('data.*.user_id', [$user->getKey()]);
    }

    /**
     * Test an ability to view own and team led users own paginated drafted quotes.
     *
     * @return void
     */
    public function testCanViewOwnAndTeamLedUsersOwnPaginatedUnifiedDraftedQuotes()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_own_quotes',
            'update_own_external_quotes',
            'update_own_internal_quotes'
        ]);

        /** @var User $teamLeader */
        $teamLeader = factory(User::class)->create();

        /** @var Team $team */
        $team = factory(Team::class)->create();

        $team->teamLeaders()->attach($teamLeader);

        $ledUser = factory(User::class)->create([
            'team_id' => $team->getKey()
        ]);

        $teamLeader->syncRoles($role);

        /** @var Quote $ownQuote */
        $ownQuote = factory(Quote::class)->create([
            'user_id' => $teamLeader->getKey(),
            'submitted_at' => null,
        ]);

        /** @var Quote $ledUserOwnQuote */
        $ledUserOwnQuote = factory(Quote::class)->create([
            'user_id' => $ledUser->getKey(),
            'submitted_at' => null,
        ]);

        $this->actingAs($teamLeader, 'api');

        $response = $this->getJson('api/unified-quotes/drafted')
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

                        'permissions' => [
                            'update',
                            'delete'
                        ]
                    ]
                ],
                'links' => [
                    'first', 'last', 'prev', 'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links' => [
                        '*' => [
                            'url', 'label', 'active'
                        ]
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('data'));

        $this->assertContainsEquals($ownQuote->getKey(), $response->json('data.*.id'));
        $this->assertContainsEquals($ledUserOwnQuote->getKey(), $response->json('data.*.id'));
        $this->assertContainsEquals($teamLeader->getKey(), $response->json('data.*.user_id'));
        $this->assertContainsEquals($ledUser->getKey(), $response->json('data.*.user_id'));
    }
}
