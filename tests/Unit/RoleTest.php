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
    use DatabaseTransactions, WithFakeUser, AssertsListing;

    /** @var \App\Contracts\Repositories\RoleRepositoryInterface */
    protected $roles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles = app('role.repository');
    }

    /**
     * Test Role listing.
     *
     * @return void
     */
    public function testRoleListing()
    {
        $response = $this->getJson(url('api/roles'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc'
        ]);

        $response = $this->getJson(url('api/roles?' . $query));

        $response->assertOk();
    }

    /**
     * Test Role creating with specified Privileges.
     *
     * @return void
     */
    public function testRoleCreating()
    {
        $attributes = factory(Role::class)->raw();

        $response = $this->postJson(url('api/roles'), $attributes);

        $response->assertOk()
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
        $attributes = factory(Role::class)->raw();

        data_set($attributes, 'privileges.0', Str::random(20));

        $response = $this->postJson(url('api/roles'), $attributes);

        $response->assertJsonStructure([
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

        $newAttributes = factory(Role::class)->raw();

        $response = $this->patchJson(url("api/roles/{$role->id}"), $newAttributes);

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'privileges', 'user_id', 'is_system', 'created_at', 'activated_at'
            ])
            ->assertJsonFragment(Arr::only($newAttributes, ['name', 'privileges']));
    }

    /**
     * Testing Updating a system defined role. Updating system defined roles is forbidden.
     *
     * @return void
     */
    public function testSystemDefinedRoleUpdating()
    {
        $role = $this->roles->findByName('Administrator');

        $attributes = factory(Role::class)->raw();

        $response = $this->patchJson(url("api/roles/{$role->id}"), $attributes);

        $response->assertForbidden();

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

        $response = $this->deleteJson(url("api/roles/{$role->id}"));

        $response->assertOk()
            ->assertExactJson([true]);
    }

    /**
     * Testing Deleting a system defined Role. Deleting system defined roles is forbidden.
     *
     * @return void
     */
    public function testSystemDefinedRoleDeleting()
    {
        $role = $this->roles->findByName('Administrator');

        $response = $this->deleteJson(url("api/roles/{$role->id}"));

        $response->assertForbidden();

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

        $response = $this->putJson(url("api/roles/activate/{$role->id}"));

        $response->assertOk()
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

        $response = $this->putJson(url("api/roles/deactivate/{$role->id}"));

        $response->assertOk()
            ->assertExactJson([true]);
    }
}
