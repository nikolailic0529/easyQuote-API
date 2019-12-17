<?php

namespace Tests\Unit\User;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};

class UserTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, AssertsListing;

    protected $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = app('user.repository');
    }

    /**
     * Test User Listing.
     *
     * @return void
     */
    public function testUserListing()
    {
        $response = $this->getJson(url('api/users'), $this->authorizationHeader);

        $this->assertListing($response);
    }

    /**
     * Test Displaying a specified User.
     *
     * @return void
     */
    public function testSpecifiedUserDisplaying()
    {
        $user = $this->userRepository->random();

        $response = $this->getJson(url("api/users/{$user->id}"), $this->authorizationHeader);

        $response->assertOk()
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
        $attributes = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->safeEmail,
            'country_id' => $this->user->country_id,
            'timezone_id' => $this->user->timezone_id,
            'role_id' => $this->user->role_id
        ];

        $response = $this->patchJson(url("api/users/{$this->user->id}"), $attributes, $this->authorizationHeader);

        $response->assertOk()->assertExactJson([true]);


        /**
         * Checking that the current model attributes are matching expected attributes.
         */
        $this->user->refresh();
        $currentAttributes = $this->user->only(array_keys($attributes));
        $this->assertEquals(0, $attributes <=> $currentAttributes);
    }

    /**
     * Test Activating a specified User.
     *
     * @return void
     */
    public function testUserActivating()
    {
        $response = $this->putJson(url("api/users/activate/{$this->user->id}"), [], $this->authorizationHeader);

        $response->assertOk()->assertExactJson([true]);

        $this->user->refresh();

        $this->assertNotNull($this->user->activated_at);
    }

    /**
     * Test Deactivating a specified User.
     *
     * @return void
     */
    public function testUserDeactivating()
    {
        $response = $this->putJson(url("api/users/deactivate/{$this->user->id}"), [], $this->authorizationHeader);

        $response->assertOk()->assertExactJson([true]);

        $this->user->refresh();

        $this->assertNull($this->user->activated_at);
    }

    /**
     * Test Self Deleting. Self deleting is forbidden.
     *
     * @return void
     */
    public function testSelfDeleting()
    {
        $response = $this->deleteJson(url("api/users/{$this->user->id}"), [], $this->authorizationHeader);

        $response->assertForbidden();
    }

    /**
     * Test Deleting a specified User.
     *
     * @return void
     */
    public function testUserDeleting()
    {
        $user = $this->createUser();

        $response = $this->deleteJson(url("api/users/{$user->id}"), [], $this->authorizationHeader);

        $response->assertOk()
            ->assertExactJson([true]);

        $user->refresh();

        $this->assertNotNull($user->deleted_at);
    }
}
