<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesUnit;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * @group worldwide
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
                        'opportunity_id',
                        'company_id',
                        'type_name',
                        'completeness',
                        'stage',
                        'created_at',
                        'updated_at',
                        'activated_at',
                        'user_fullname',
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
                //

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
                        'user_fullname',
                        'company_name',
                        'customer_name',
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
                //

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
     * Test an ability to view own paginated submitted worldwide quotes.
     */
    public function testCanViewOwnPaginatedSubmittedWorldwideQuotes(): void
    {
        /** @var Role $role */
        $role = Role::factory()->create();

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
     * Test an ability to view only drafted quotes belong to assigned sales units to user.
     */
    public function testCanViewDraftedQuotesBelongToAssignedSalesUnitsToUser(): void
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

}
