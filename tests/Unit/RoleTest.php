<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Unit\Traits\AssertsListing;
use Tests\Unit\Traits\WithFakeUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Str, Arr;

class RoleTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, AssertsListing;

    protected $roleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleRepository = app('role.repository');
    }

    /**
     * Test Role listing.
     *
     * @return void
     */
    public function testRoleListing()
    {
        $response = $this->getJson(url('api/roles'), $this->authorizationHeader);

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
        $attributes = $this->makeGenericRoleAttributes();

        $response = $this->postJson(url('api/roles'), $attributes, $this->authorizationHeader);

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
    public function testRoleCreatingWithWrongAttributes()
    {
        $attributesWithWrongPrivileges = $this->makeGenericRoleAttributes();

        data_set($attributesWithWrongPrivileges, 'privileges.0', Str::random(20));

        $response = $this->postJson(url('api/roles'), $attributesWithWrongPrivileges, $this->authorizationHeader);

        $response->assertJsonValidationErrors('privileges.0.privilege');
    }

    /**
     * Test Updating a newly created Role.
     *
     * @return void
     */
    public function testRoleUpdating()
    {
        $attributes = $this->makeGenericRoleAttributes();

        $role = $this->roleRepository->create($attributes);

        $newAttributes = $this->makeGenericRoleAttributes();

        $response = $this->patchJson(url("api/roles/{$role->id}"), $newAttributes, $this->authorizationHeader);

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
        $role = $this->roleRepository->findByName('Administrator');

        $attributes = $this->makeGenericRoleAttributes();

        $response = $this->patchJson(url("api/roles/{$role->id}"), $attributes, $this->authorizationHeader);

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
        $role = $this->roleRepository->create($this->makeGenericRoleAttributes());

        $response = $this->deleteJson(url("api/roles/{$role->id}"), [], $this->authorizationHeader);

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
        $role = $this->roleRepository->findByName('Administrator');

        $response = $this->deleteJson(url("api/roles/{$role->id}"), [], $this->authorizationHeader);

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
        $role = $this->roleRepository->create($this->makeGenericRoleAttributes());

        $response = $this->putJson(url("api/roles/activate/{$role->id}"), [], $this->authorizationHeader);

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
        $role = $this->roleRepository->create($this->makeGenericRoleAttributes());

        $response = $this->putJson(url("api/roles/deactivate/{$role->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);
    }

    protected function makeGenericRoleAttributes(): array
    {
        $modulePrivileges = collect(config('role.modules'))->eachKeys();
        $modules = array_keys(config('role.modules'));

        $privileges = collect($modules)->transform(function ($module) use ($modulePrivileges) {
            $privilege = collect($modulePrivileges->get($module))->random();
            return compact('module', 'privilege');
        })->toArray();

        return [
            'name' => Str::random(40),
            'privileges' => $privileges,
            'user_id' => $this->user->id
        ];
    }
}
