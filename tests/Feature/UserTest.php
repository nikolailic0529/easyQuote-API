<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class UserTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test an ability to view paginated users.
     *
     * @return void
     */
    public function testCanViewPaginatedUsers()
    {
        $this->authenticateApi();

        $this->getJson('api/users')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'email',
                        'team_name',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'role_name',
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

        $query = http_build_query([
            'search' => Str::random(10),
            'order_by_created_at' => 'asc',
            'order_by_name' => 'asc',
            'order_by_role' => 'asc',
            'order_by_firstname' => 'asc',
            'order_by_lastname' => 'asc',
        ]);

        $this->getJson('api/users?'.$query)->assertOk();
    }

    /**
     * Test an ability to view an existing user.
     *
     * @return void
     */
    public function testCanViewUser()
    {
        $this->authenticateApi();
        $user = factory(User::class)->create();

        $this->getJson("api/users/".$user->getKey())
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
                'privileges',
            ]);
    }

    /**
     * Test can update an existing user.
     *
     * @return void
     */
    public function testCanUpdateUser()
    {
        $this->authenticateApi();

        $user = factory(User::class)->create();

        $this->patchJson("api/users/".$user->getKey(), [
            'first_name' => $firstName = preg_replace('/[^[:alpha:]]/', '', $this->faker->firstName),
            'middle_name' => $middleName = null,
            'last_name' => $lastName = preg_replace('/[^[:alpha:]]/', '', $this->faker->lastName),
            'team_id' => $teamKey = UT_EPD_WW
        ])
//            ->dump()
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/users/'.$user->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'team' => [
                    'id', 'team_name'
                ],
                'team_id',
            ])
            ->assertJson([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'team_id' => $teamKey,
            ]);
    }

    /**
     * Test an ability to update an email of an existing user.
     *
     * @return void
     */
    public function testCanNotUpdateEmailOfUser()
    {
        $this->authenticateApi();

        $user = factory(User::class)->create();

        $this->patchJson("api/users/".$user->getKey(), ['email' => $this->faker->unique()->safeEmail])
            ->assertForbidden();
    }

    /**
     * Test an ability to activate an existing user.
     *
     * @return void
     */
    public function testCanActivateUser()
    {
        $this->authenticateApi();

        $user = factory(User::class)->create();
        $user->activated_at = null;
        $user->save();

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson("api/users/activate/".$user->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to deactivate an existing user.
     *
     * @return void
     */
    public function testCanDeactivateUser()
    {
        $this->authenticateApi();

        $user = factory(User::class)->create();
        $user->activated_at = now();
        $user->save();

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson("api/users/deactivate/".$user->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at'
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to delete own account.
     *
     * @return void
     */
    public function testCanNotDeleteOwnAccount()
    {
        $this->authenticateApi();

        $this->deleteJson("api/users/".$this->app['auth.driver']->id())
            ->assertForbidden();
    }

    /**
     * Test an ability to delete other existing user.
     *
     * @return void
     */
    public function testCanDeleteOtherUser()
    {
        $this->authenticateApi();

        $user = factory(User::class)->create();

        $this->deleteJson("api/users/".$user->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/users/'.$user->getKey())
            ->assertNotFound();
    }
}
