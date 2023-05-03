<?php

namespace Tests\Feature;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\Authorization\Enum\AccessEntityPipelineDirection;
use App\Domain\Authorization\Models\Permission;
use App\Domain\Authorization\Models\Role;
use App\Domain\Pipeline\Models\Pipeline;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class RoleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view a listing of existing roles.
     */
    public function testCanViewListingOfRoles(): void
    {
        $this->authenticateApi();

        $this->getJson('api/roles')
//        ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                        'activated_at',
                        'users_count',
                    ],
                ],
                'current_page',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_role' => 'asc',
        ]);

        $this->getJson('api/roles?'.$query)->assertOk();
    }

    public function testCanViewRole(): void
    {
        $this->authenticateApi();

        /** @var Role $role */
        $role = Role::factory()->create([
            'access' => [
                'allowedOpportunityPipelines' => [
                    ['pipelineId' => Pipeline::factory()->create()->getKey()],
                ],
            ],
        ]);
        $allPermissions = Permission::query()->where('guard_name', 'api')->get();
        $role->syncPermissions($allPermissions);

        $this->getJson('api/roles/'.$role->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'privileges' => [
                    '*' => [
                        'module',
                        'privilege',
                        'submodules' => [
                            '*' => [
                                'submodule',
                                'privilege',
                            ],
                        ],
                    ],
                ],
                'access_data' => [
                    'access_contact_direction',
                    'access_company_direction',
                    'access_opportunity_direction',
                    'access_opportunity_pipeline_direction',
                    'allowed_opportunity_pipelines' => [
                        '*' => [
                            'pipeline_id',
                            'pipeline_name',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(1, 'access_data.allowed_opportunity_pipelines');
    }

    /**
     * Test an ability to create a new role with valid attributes.
     *
     * @dataProvider roleDataProvider
     */
    public function testCanCreateNewRole(\Closure $getData): void
    {
        $this->authenticateApi();

        $data = $getData();

        $r = $this->postJson('api/roles', $data)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'privileges', 'created_at', 'activated_at',
            ]);

        $this->getJson('api/roles/'.$r->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'is_system',
                'created_at',
                'activated_at',
                'privileges' => [
                    '*' => [
                        'module',
                        'privilege',
                        'submodules' => [
                            '*' => [
                                'submodule',
                                'privilege',
                            ],
                        ],
                    ],
                ],
                'access_data' => [
                    'access_contact_direction',
                    'access_company_direction',
                    'access_opportunity_direction',
                    'access_worldwide_quote_direction',
                    'access_sales_order_direction',
                ],
            ])
            ->assertJsonPath('name', $data['name']);

        if (isset($data['access_data'])) {
            foreach ($data['access_data'] as $name => $value) {
                $this->assertSame($value, $r->json("access_data.$name"), $name);
            }
        }

        $dataPrivilegesMap = $this->privilegesToMap($data['privileges']);
        $responsePrivilegesMap = $this->privilegesToMap($r->json('privileges'));

        $this->assertEquals($dataPrivilegesMap, $responsePrivilegesMap);
    }

    /**
     * Test an ability to create a new role with invalid attributes.
     */
    public function testCanNotCreateNewRoleWithInvalidAttributes(): void
    {
        $this->authenticateApi();

        $attributes = \factory(Role::class)->state('privileges')->raw();

        \data_set($attributes, 'privileges.0', Str::random(20));

        $this->postJson('api/roles', $attributes)
            ->assertJsonStructure([
                'Error' => ['original' => ['privileges.0.privilege']],
            ]);
    }

    /**
     * Test an ability to update an existing role.
     *
     * @dataProvider roleDataProvider
     */
    public function testCanUpdateExistingRole(\Closure $getData): void
    {
        $this->authenticateApi();

        $role = Role::factory()->create();

        $data = $getData();

        $r = $this->patchJson("api/roles/{$role->getKey()}", $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'user_id',
                'is_system',
                'created_at',
                'activated_at',
                'privileges' => [
                    '*' => [
                        'module',
                        'privilege',
                        'submodules' => [
                            '*' => [
                                'submodule',
                                'privilege',
                            ],
                        ],
                    ],
                ],
                'access_data' => [
                    'access_contact_direction',
                    'access_company_direction',
                    'access_opportunity_direction',
                    'access_worldwide_quote_direction',
                    'access_sales_order_direction',
                ],
            ])
            ->assertJsonPath('name', $data['name']);

        if (isset($data['access_data'])) {
            foreach ($data['access_data'] as $name => $value) {
                $this->assertSame($value, $r->json("access_data.$name"), $name);
            }
        }

        $dataPrivilegesMap = $this->privilegesToMap($data['privileges']);
        $responsePrivilegesMap = $this->privilegesToMap($r->json('privileges'));

        $this->assertEquals($dataPrivilegesMap, $responsePrivilegesMap);
    }

    protected function roleDataProvider(): iterable
    {
        yield 'only privileges' => [
            function (): array {
                return [
                    'name' => Str::random(60),
                    'privileges' => $this->randomRolePrivileges(),
                ];
            },
        ];

        $directions = [
            'access_contact_direction',
            'access_company_direction',
            'access_opportunity_direction',
            'access_worldwide_quote_direction',
            'access_sales_order_direction',
        ];

        foreach (AccessEntityDirection::cases() as $case) {
            foreach ($directions as $module) {
                yield "access data: $module: $case->value" => [
                    function () use ($module, $case): array {
                        return [
                            'name' => Str::random(60),
                            'privileges' => $this->randomRolePrivileges(),
                            'access_data' => [
                                $module => $case->value,
                            ],
                        ];
                    },
                ];
            }
        }

        yield 'access data: access_opportunity_pipeline_direction: all' => [
            function () {
                return [
                    'name' => Str::random(60),
                    'privileges' => $this->randomRolePrivileges(),
                    'access_data' => [
                        'access_opportunity_pipeline_direction' => AccessEntityPipelineDirection::All->value,
                    ],
                ];
            },
        ];

        yield 'access data: access_opportunity_pipeline_direction: selected' => [
            function () {
                $pipelines = Pipeline::factory()->count(2)->create();

                return [
                    'name' => Str::random(60),
                    'privileges' => $this->randomRolePrivileges(),
                    'access_data' => [
                        'access_opportunity_pipeline_direction' => AccessEntityPipelineDirection::Selected->value,
                        'allowed_opportunity_pipelines' => $pipelines->map(static function (Pipeline $pipeline): array {
                            return [
                                'pipeline_id' => $pipeline->getKey(),
                                'pipeline_name' => $pipeline->pipeline_name,
                            ];
                        })
                            ->all(),
                    ],
                ];
            },
        ];
    }

    private function privilegesToMap(array $privileges): array
    {
        return collect($privileges)
            ->lazy()
            ->map(static function (array $module): array {
                $submodules = collect($module['submodules'])
                    ->pluck('privilege', 'submodule')
                    ->all();

                $module['submodules'] = $submodules;

                return $module;
            })
            ->keyBy('module')
            ->all();
    }

    private function randomRolePrivileges(): array
    {
        $modulePrivileges = collect(config('role.modules'))
            ->map(static fn (array $rights): array => array_keys($rights));
        $modules = array_keys(config('role.modules'));
        $submodules = config('role.submodules');

        return collect($modules)
            ->map(static function (string $mName) use ($modulePrivileges, $submodules): array {
                $sub = collect($submodules[$mName] ?? [])
                    ->map(static function (array $privileges, string $subModuleName): array {
                        return ['submodule' => $subModuleName, 'privilege' => Arr::random(array_keys($privileges))];
                    })
                    ->values()
                    ->all();

                return [
                    'module' => $mName,
                    'privilege' => Arr::random($modulePrivileges[$mName]),
                    'submodules' => $sub,
                ];
            })
            ->all();
    }

    /**
     * Test an ability to update direct permissions of an existing role.
     */
    public function testCanUpdateDirectPermissionsOfExistingRole(): void
    {
        $this->authenticateApi();

        $role = \factory(Role::class)->create();

        $attributes = \factory(Role::class)->state('privileges')->raw();

        $attributes = array_merge($attributes, [
            'properties' => array_fill_keys([
                'download_quote_pdf',
                'download_quote_price',
                'download_quote_schedule',
                'download_contract_pdf',
                'download_hpe_contract_pdf',
                'download_ww_quote_pdf',
                'download_ww_quote_distributor_file',
                'download_ww_quote_payment_schedule',
                'download_sales_order_pdf',
                'cancel_sales_orders',
                'resubmit_sales_orders',
                'unravel_sales_orders',
                'alter_active_status_of_sales_orders',
            ], true),
        ]);

        $this->patchJson('api/roles/'.$role->getKey(), $attributes)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'user_id', 'is_system', 'created_at', 'activated_at',
                'privileges' => [
                    '*' => [
                        'module',
                        'privilege',
                        'submodules' => [
                            '*' => [
                                'submodule',
                                'privilege',
                            ],
                        ],
                    ],
                ],
            ]);

        $response = $this->getJson('api/roles/'.$role->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'user_id', 'is_system', 'created_at', 'activated_at',
                'privileges' => [
                    '*' => [
                        'module',
                        'privilege',
                        'submodules' => [
                            '*' => [
                                'submodule',
                                'privilege',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonFragment(Arr::only($attributes, ['name', 'privileges']));

        foreach ($attributes['properties'] as $key => $value) {
            $this->assertSame($value, $response->json('properties.'.$key));
        }

        $attributes = array_merge($attributes, [
            'properties' => array_fill_keys([
                'download_quote_pdf',
                'download_quote_price',
                'download_quote_schedule',
                'download_contract_pdf',
                'download_hpe_contract_pdf',
                'download_ww_quote_pdf',
                'download_ww_quote_distributor_file',
                'download_ww_quote_payment_schedule',
                'download_sales_order_pdf',
                'cancel_sales_orders',
                'resubmit_sales_orders',
                'unravel_sales_orders',
                'alter_active_status_of_sales_orders',
            ], false),
        ]);

        $this->patchJson('api/roles/'.$role->getKey(), $attributes)
            ->assertOk();

        $response = $this->getJson('api/roles/'.$role->getKey())
            ->assertOk();

        foreach ($attributes['properties'] as $key => $value) {
            $this->assertSame($value, $response->json('properties.'.$key));
        }
    }

    /**
     * Test an ability to update a system defined role.
     */
    public function testCanNotUpdateSystemDefinedRole(): void
    {
        $this->authenticateApi();

        $role = \factory(Role::class)->create(['is_system' => true]);

        $attributes = \factory(Role::class)->state('privileges')->raw();

        $response = $this->patchJson('api/roles/'.$role->getKey(), $attributes)
//            ->dump()
            ->assertForbidden();

        $this->assertEquals(\RSU_01, $response->json('message'));
    }

    /**
     * Test an ability to delete an existing role.
     */
    public function testCanDeleteExistingRole(): void
    {
        $this->authenticateApi();

        $role = Role::factory()->create();

        $this->deleteJson('api/roles/'.$role->getKey())
            ->assertNoContent();
    }

    /**
     * Test an ability to delete a system defined role.
     */
    public function testCanNotDeleteSystemDefinedRole(): void
    {
        $this->authenticateApi();

        $role = Role::factory()->create(['is_system' => true]);

        $response = $this->deleteJson('api/roles/'.$role->getKey())->assertForbidden();

        $this->assertEquals(\RSD_01, $response->json('message'));
    }

    /**
     * Test an ability to mark a role as active.
     */
    public function testCanMarkRoleAsActive(): void
    {
        $this->authenticateApi();

        $role = \tap(Role::factory()->create(), static function (Role $role): void {
            $role->activated_at = null;

            $role->save();
        });

        $this->putJson('api/roles/activate/'.$role->getKey())
            ->assertNoContent();
    }

    /**
     * Test an ability to mark a role as inactive.
     */
    public function testCanMarkRoleAsInactive(): void
    {
        $this->authenticateApi();

        $role = \tap(Role::factory()->create(), static function (Role $role): void {
            $role->activated_at = now();

            $role->save();
        });

        $this->putJson('api/roles/deactivate/'.$role->getKey())
//            ->dump()
            ->assertNoContent();
    }
}
