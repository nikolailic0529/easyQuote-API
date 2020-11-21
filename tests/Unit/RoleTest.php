<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    AssertsListing,
    WithFakeUser
};
use App\Models\Role;
use Str, Arr;

class RoleTest extends TestCase
{
    use WithFakeUser, AssertsListing;

    /**
     * Test Role listing.
     *
     * @return void
     */
    public function testRoleListing()
    {
        $response = $this->getJson('api/roles');

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_role' => 'asc'
        ]);

        $this->getJson(url('api/roles?' . $query))->assertOk();
    }

    /**
     * Test Role creating with specified Privileges.
     *
     * @return void
     */
    public function testRoleCreating()
    {
        $attributes = factory(Role::class)->state('privileges')->raw();

        $this->postJson('api/roles', $attributes)
            ->assertOk()
            ->assertJsonStructure([
                'id', 'privileges', 'created_at', 'activated_at'
            ]);
    }

    /**
     * Test Role creating with invalid attributes.
     *
     * @return void
     */
    public function testRoleCreatingWithInvalidAttributes()
    {
        $attributes = factory(Role::class)->state('privileges')->raw();

        data_set($attributes, 'privileges.0', Str::random(20));

        $this->postJson('api/roles', $attributes)
            ->assertJsonStructure([
                'Error' => ['original' => ['privileges.0.privilege']]
            ]);
    }

    /**
     * Test Updating a newly created Role.
     *
     * @return void
     */
    public function testRoleUpdating()
    {
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
                            ]
                        ]
                    ]
                ],
            ])
            ->assertJsonFragment(Arr::only($attributes, ['name', 'privileges']));
    }

    /**
     * Testing Updating a system defined role. Updating system defined roles is forbidden.
     *
     * @return void
     */
    public function testSystemDefinedRoleUpdating()
    {
        $role = app('role.repository')->findByName('Administrator');

        $attributes = factory(Role::class)->raw();

        $response = $this->patchJson("api/roles/{$role->id}", $attributes)->assertForbidden();

        $this->assertEquals(RSU_01, $response->json('message'));
    }

    /**
     * Testing Deleting a newly created Role.
     *
     * @return void
     */
    public function testRoleDeleting()
    {
        $role = factory(Role::class)->create();

        $this->deleteJson("api/roles/{$role->id}")
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Testing Deleting a system defined Role. Deleting system defined roles is forbidden.
     *
     * @return void
     */
    public function testSystemDefinedRoleDeleting()
    {
        $role = app('role.repository')->findByName('Administrator');

        $response = $this->deleteJson("api/roles/{$role->id}")->assertForbidden();

        $this->assertEquals(RSD_01, $response->json('message'));
    }

    /**
     * Testing Activating a newly created Role.
     *
     * @return void
     */
    public function testRoleActivating()
    {
        $role = tap(factory(Role::class)->create())->deactivate();

        $this->putJson("api/roles/activate/{$role->id}")
            ->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Testing Deactivating a newly created Role.
     *
     * @return void
     */
    public function testRoleDeactivating()
    {
        $role = tap(factory(Role::class)->create())->activate();

        $this->putJson("api/roles/deactivate/{$role->id}")
            ->assertOk()
            ->assertExactJson([true]);
    }
}
