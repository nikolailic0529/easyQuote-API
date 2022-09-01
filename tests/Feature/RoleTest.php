<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\{Arr, Str};
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing};
use function data_set;
use function factory;
use function tap;
use function url;
use const RSD_01;
use const RSU_01;

/**
 * @group build
 */
class RoleTest extends TestCase
{
    use AssertsListing, DatabaseTransactions;

    /**
     * Test an ability to view a listing of existing roles.
     *
     * @return void
     */
    public function testCanViewListingOfRoles()
    {
        $this->authenticateApi();

        $response = $this->getJson('api/roles');

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_role' => 'asc',
        ]);

        $this->getJson(url('api/roles?'.$query))->assertOk();
    }

    /**
     * Test an ability to create a new role with valid attributes.
     *
     * @return void
     */
    public function testCanCreateNewRole()
    {
        $this->authenticateApi();

        $attributes = factory(Role::class)->state('privileges')->raw();

        $this->postJson('api/roles', $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'privileges', 'created_at', 'activated_at',
            ]);
    }

    /**
     * Test an ability to create a new role with invalid attributes.
     *
     * @return void
     */
    public function testCanNotCreateNewRoleWithInvalidAttributes()
    {
        $this->authenticateApi();

        $attributes = factory(Role::class)->state('privileges')->raw();

        data_set($attributes, 'privileges.0', Str::random(20));

        $this->postJson('api/roles', $attributes)
            ->assertJsonStructure([
                'Error' => ['original' => ['privileges.0.privilege']],
            ]);
    }

    /**
     * Test an ability to update an existing role.
     *
     * @return void
     */
    public function testCanUpdateExistingRole()
    {
        $this->authenticateApi();

        $role = factory(Role::class)->create();

        $attributes = factory(Role::class)->state('privileges')->raw();

        $this->patchJson("api/roles/{$role->id}", $attributes)
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
    }

    /**
     * Test an ability to update direct permissions of an existing role.
     *
     * @return void
     */
    public function testCanUpdateDirectPermissionsOfExistingRole()
    {
        $this->authenticateApi();

        $role = factory(Role::class)->create();

        $attributes = factory(Role::class)->state('privileges')->raw();

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
            ], true)
        ]);

        $this->patchJson("api/roles/".$role->getKey(), $attributes)
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
            ], false)
        ]);

        $this->patchJson("api/roles/".$role->getKey(), $attributes)
            ->assertOk();

        $response = $this->getJson('api/roles/'.$role->getKey())
            ->assertOk();

        foreach ($attributes['properties'] as $key => $value) {
            $this->assertSame($value, $response->json('properties.'.$key));
        }
    }

    /**
     * Test an ability to update a system defined role.
     *
     * @return void
     */
    public function testCanNotUpdateSystemDefinedRole()
    {
        $this->authenticateApi();

        $role = factory(Role::class)->create(['is_system' => true]);

        $attributes = factory(Role::class)->state('privileges')->raw();

        $response = $this->patchJson('api/roles/'.$role->getKey(), $attributes)
//            ->dump()
            ->assertForbidden();

        $this->assertEquals(RSU_01, $response->json('message'));
    }

    /**
     * Test an ability to delete an existing role.
     *
     * @return void
     */
    public function testCanDeleteExistingRole()
    {
        $this->authenticateApi();

        $role = factory(Role::class)->create();

        $this->deleteJson("api/roles/".$role->getKey())
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test an ability to delete a system defined role.
     *
     * @return void
     */
    public function testCanNotDeleteSystemDefinedRole()
    {
        $this->authenticateApi();

        $role = factory(Role::class)->create(['is_system' => true]);

        $response = $this->deleteJson('api/roles/'.$role->getKey())->assertForbidden();

        $this->assertEquals(RSD_01, $response->json('message'));
    }

    /**
     * Test an ability to mark a role as active.
     *
     * @return void
     */
    public function testCanMarkRoleAsActive()
    {
        $this->authenticateApi();

        $role = tap(factory(Role::class)->create(), function (Role $role) {
            $role->activated_at = null;

            $role->save();
        });

        $this->putJson("api/roles/activate/".$role->getKey())
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Test an ability to mark a role as inactive.
     *
     * @return void
     */
    public function testCanMarkRoleAsInactive()
    {
        $this->authenticateApi();

        $role = tap(factory(Role::class)->create(), function (Role $role) {
            $role->activated_at = now();

            $role->save();
        });

        $this->putJson("api/roles/deactivate/".$role->getKey())
//            ->dump()
            ->assertOk()
            ->assertExactJson([true]);
    }
}
