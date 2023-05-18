<?php

namespace Tests\Feature;

use App\Domain\Authorization\Models\Role;
use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\Rescue\Models\Contract;
use App\Domain\User\Models\User;
use Elasticsearch\Client as Elasticsearch;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Ring\Client\MockHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * @group build
 */
class UnifiedContractTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view a list of contract user names.
     *
     * @return void
     */
    public function testCanViewListOfContractUserNames(): void
    {
        $this->authenticateApi();

        factory(Contract::class)->create();

        $this->getJson('api/contracts/drafted/users')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);

        $this->getJson('api/contracts/submitted/users')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Test an ability to view a list of contract customer names.
     *
     * @return void
     */
    public function testCanViewListOfContractCustomerNames(): void
    {
        $this->authenticateApi();

        factory(Contract::class)->create();

        $this->getJson('api/contracts/drafted/customers')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);

        $this->getJson('api/contracts/submitted/customers')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Test an ability to view a list of contract numbers.
     *
     * @return void
     */
    public function testCanViewListOfContractNumbers(): void
    {
        $this->authenticateApi();

        factory(Contract::class)->create();

        $this->getJson('api/contracts/drafted/contract-numbers')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);

        $this->getJson('api/contracts/submitted/contract-numbers')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Test an ability to view a list of contract numbers.
     *
     * @return void
     */
    public function testCanViewListOfContractCompanyNames(): void
    {
        $this->authenticateApi();

        factory(Contract::class)->create();

        $this->getJson('api/contracts/drafted/companies')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);

        $this->getJson('api/contracts/submitted/companies')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Test an ability to view paginated unified drafted contracts.
     *
     * @return void
     */
    public function testCanViewPaginatedDraftedContracts(): void
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('quotes')->delete();
        $this->app['db.connection']->table('hpe_contracts')->delete();

        factory(HpeContract::class)->create();
        factory(Contract::class)->create();

        $this->getJson('api/contracts/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quote_id',
                        'type',
                        'user' => [
                            'id', 'first_name', 'last_name',
                        ],
                        'company' => [
                            'id', 'name',
                        ],
                        'contract_customer' => [
                            'rfq',
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'completeness',
                        'last_drafted_step',
                        'quote_customer' => [
                            'id',
                            'name',
                            'rfq',
                            'valid_until',
                            'support_start',
                            'support_end',
                        ],
                        'created_at',
                        'updated_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    /**
     * Test an ability to view paginated unified drafted contracts.
     *
     * @return void
     */
    public function testCanViewOwnPaginatedDraftedContracts(): void
    {
        $this->app['db.connection']->table('quotes')->delete();
        $this->app['db.connection']->table('hpe_contracts')->delete();

        factory(HpeContract::class)->create([
            'user_id' => User::factory()->create()->getKey(),
        ]);
        factory(Contract::class)->create([
            'user_id' => User::factory()->create()->getKey(),
        ]);

        /** @var \App\Domain\User\Models\User $user */
        $user = User::factory()->create();

        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(
            'view_own_contracts'
        );

        $user->syncRoles($role);

        $this->actingAs($user, 'api');

        $ownHpeContract = factory(HpeContract::class)->create([
            'user_id' => $user->getKey(),
        ]);
        $ownRescueContract = factory(Contract::class)->create([
            'user_id' => $user->getKey(),
        ]);

        $response = $this->getJson('api/contracts/drafted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quote_id',
                        'type',
                        'user' => [
                            'id', 'first_name', 'last_name',
                        ],
                        'company' => [
                            'id', 'name',
                        ],
                        'contract_customer' => [
                            'rfq',
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'completeness',
                        'last_drafted_step',
                        'quote_customer' => [
                            'id',
                            'name',
                            'rfq',
                            'valid_until',
                            'support_start',
                            'support_end',
                        ],
                        'created_at',
                        'updated_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        // Contract viewing is allowed regardless of ownership
        $this->assertCount(4, $response->json('data'));

        $countWhereOwner = collect($response->json('data'))
            ->lazy()
            ->whereStrict('user.id', $user->getKey())
            ->count();

        $this->assertSame(2, $countWhereOwner);
    }

    /**
     * Test an ability to filter unified drafted contracts.
     *
     * @return void
     */
    public function testCanFilterDraftedContracts(): void
    {
        $this->authenticateApi();

        $contracts = factory(Contract::class, 10)->create();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, json_encode([
            'hits' => [
                'hits' => array_map(static fn (Contract $contract) => ['_id' => $contract->getKey()], $contracts->all()),
            ],
        ]));
        rewind($stream);

        $handler = new MockHandler([
            'status' => 200,
            'transfer_stats' => [
                'total_time' => 100,
            ],
            'body' => $stream,
            'effective_url' => 'localhost',
        ]);

        $esClient = ClientBuilder::create()
            ->setHosts(['testing'])
            ->setHandler($handler)
            ->build();

        $this->app->instance(Elasticsearch::class, $esClient);

        $customerNames = $contracts->map(static fn (Contract $contract) => $contract->customer->name)->all();

        $this->getJson('api/contracts/drafted?'.Arr::query([
                'filter' => [
                    'eq' => [
                        'customer_name' => $customerNames,
                    ],
                    'gte' => [
                        'created_at' => '2021-01-01',
                    ],
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quote_id',
                        'type',
                        'user' => [
                            'id', 'first_name', 'last_name',
                        ],
                        'company' => [
                            'id', 'name',
                        ],
                        'contract_customer' => [
                            'rfq',
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'completeness',
                        'last_drafted_step',
                        'quote_customer' => [
                            'id',
                            'name',
                            'rfq',
                            'valid_until',
                            'support_start',
                            'support_end',
                        ],
                        'created_at',
                        'updated_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    /**
     * Test an ability to filter unified submitted contracts.
     *
     * @return void
     */
    public function testCanFilterSubmittedContracts(): void
    {
        $this->authenticateApi();

        $contracts = factory(Contract::class, 10)->create([
            'submitted_at' => now(),
        ]);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, json_encode([
            'hits' => [
                'hits' => array_map(static fn (Contract $contract) => ['_id' => $contract->getKey()], $contracts->all()),
            ],
        ]));
        rewind($stream);

        $handler = new MockHandler([
            'status' => 200,
            'transfer_stats' => [
                'total_time' => 100,
            ],
            'body' => $stream,
            'effective_url' => 'localhost',
        ]);

        $esClient = ClientBuilder::create()
            ->setHosts(['testing'])
            ->setHandler($handler)
            ->build();

        $this->app->instance(Elasticsearch::class, $esClient);

        $customerNames = $contracts->map(static fn (Contract $contract) => $contract->customer->name)->all();

        $this->getJson('api/contracts/submitted?'.Arr::query([
                'filter' => [
                    'eq' => [
                        'customer_name' => $customerNames,
                    ],
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quote_id',
                        'type',
                        'user' => [
                            'id', 'first_name', 'last_name',
                        ],
                        'company' => [
                            'id', 'name',
                        ],
                        'contract_customer' => [
                            'rfq',
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'quote_customer' => [
                            'id',
                            'name',
                            'rfq',
                            'valid_until',
                            'support_start',
                            'support_end',
                        ],
                        'created_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    /**
     * Test an ability to view paginated unified submitted contracts.
     *
     * @return void
     */
    public function testCanViewPaginatedSubmittedContracts(): void
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('quotes')->delete();
        $this->app['db.connection']->table('hpe_contracts')->delete();

        factory(HpeContract::class)->create([
            'submitted_at' => now(),
        ]);
        factory(Contract::class)->create([
            'submitted_at' => now(),
        ]);

        $this->getJson('api/contracts/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quote_id',
                        'type',
                        'user' => [
                            'id', 'first_name', 'last_name',
                        ],
                        'company' => [
                            'id', 'name',
                        ],
                        'contract_customer' => [
                            'rfq',
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'quote_customer' => [
                            'id',
                            'name',
                            'rfq',
                            'valid_until',
                            'support_start',
                            'support_end',
                        ],
                        'created_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    /**
     * Test an ability to view paginated unified submitted contracts.
     *
     * @return void
     */
    public function testCanViewOwnPaginatedSubmittedContracts(): void
    {
        $this->app['db.connection']->table('quotes')->delete();
        $this->app['db.connection']->table('hpe_contracts')->delete();

        factory(HpeContract::class)->create([
            'user_id' => User::factory()->create()->getKey(),
            'submitted_at' => now(),
        ]);
        factory(Contract::class)->create([
            'user_id' => User::factory()->create()->getKey(),
            'submitted_at' => now(),
        ]);

        /** @var \App\Domain\User\Models\User $user */
        $user = User::factory()->create();

        /** @var \App\Domain\Authorization\Models\Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(
            'view_own_contracts'
        );

        $user->syncRoles($role);

        $this->actingAs($user, 'api');

        $ownHpeContract = factory(HpeContract::class)->create([
            'user_id' => $user->getKey(),
            'submitted_at' => now(),
        ]);
        $ownRescueContract = factory(Contract::class)->create([
            'user_id' => $user->getKey(),
            'submitted_at' => now(),
        ]);

        $response = $this->getJson('api/contracts/submitted')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quote_id',
                        'type',
                        'user' => [
                            'id', 'first_name', 'last_name',
                        ],
                        'company' => [
                            'id', 'name',
                        ],
                        'contract_customer' => [
                            'rfq',
                        ],
                        'permissions' => [
                            'view', 'update', 'delete',
                        ],
                        'quote_customer' => [
                            'id',
                            'name',
                            'rfq',
                            'valid_until',
                            'support_start',
                            'support_end',
                        ],
                        'created_at',
                        'activated_at',
                    ],
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        // Contract viewing is allowed regardless of ownership
        $this->assertCount(4, $response->json('data'));

        $countWhereOwner = collect($response->json('data'))
            ->lazy()
            ->whereStrict('user.id', $user->getKey())
            ->count();

        $this->assertSame(2, $countWhereOwner);
    }
}
