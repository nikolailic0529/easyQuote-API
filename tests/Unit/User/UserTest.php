<?php

namespace Tests\Unit\User;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing,
    TruncatesDatabaseTables
};
use Str;

class UserTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, AssertsListing, TruncatesDatabaseTables;

    protected $truncatableTables = ['users'];

    /**
     * Test User Listing.
     *
     * @return void
     */
    public function testUserListing()
    {
        $response = $this->getJson(url('api/users'));

        $this->assertListing($response);

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_role' => 'asc',
            'order_by_firstname' => 'asc',
            'order_by_lastname' => 'asc'
        ]);

        $this->getJson(url('api/users?' . $query))->assertOk();
    }

    /**
     * Test Displaying a specified User.
     *
     * @return void
     */
    public function testSpecifiedUserDisplaying()
    {
        $user = app('user.repository')->random();

        $this->getJson(url("api/users/{$user->id}"), $this->authorizationHeader)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'email',
                'first_name',
                'middle_name',
                'last_name',
                'phone',
                'timezone_id',
                'role_id',
                'created_at',
                'activated_at',
                'picture',
                'privileges'
            ]);
    }

    /**
     * Test Updating a specified User.
     *
     * @return void
     */
    public function testUserUpdating()
    {
        $attributes = factory(User::class)->make()->only('first_name', 'last_name', 'timezone_id');

        $this->patchJson(url("api/users/{$this->user->id}"), $attributes)
            ->assertOk()->assertExactJson([true]);


        /**
         * Checking that the current model attributes are matching expected attributes.
         */
        $this->user->refresh();

        $currentAttributes = $this->user->only(array_keys($attributes));

        $this->assertEquals(0, $attributes <=> $currentAttributes);
    }

    /**
     * Test Updating email of User with Administrator role. It's not allowed.
     *
     * @return void
     */
    public function testAdministratorEmailUpdating()
    {
        $this->patchJson(url("api/users/{$this->user->id}"), ['email' => $this->faker->unique()->safeEmail])
            ->assertForbidden();
    }

    /**
     * Test Activating a specified User.
     *
     * @return void
     */
    public function testUserActivating()
    {
        $this->user->deactivate();

        $this->putJson(url("api/users/activate/{$this->user->id}"))->assertOk()->assertExactJson([true]);

        $this->assertNotNull($this->user->refresh()->activated_at);
    }

    /**
     * Test Deactivating a specified User.
     *
     * @return void
     */
    public function testUserDeactivating()
    {
        $this->user->activate();

        $this->putJson(url("api/users/deactivate/{$this->user->id}"))->assertOk()->assertExactJson([true]);

        $this->assertNull($this->user->refresh()->activated_at);
    }

    /**
     * Test Self Deleting. Self deleting is forbidden.
     *
     * @return void
     */
    public function testSelfDeleting()
    {
        $this->deleteJson(url("api/users/{$this->user->id}"))->assertForbidden();
    }

    /**
     * Test Deleting a specified User.
     *
     * @return void
     */
    public function testUserDeleting()
    {
        $user = $this->createUser();

        $this->deleteJson(url("api/users/{$user->id}"))->assertOk()->assertExactJson([true]);

        $this->assertSoftDeleted($user);
    }
}
