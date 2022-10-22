<?php

namespace Tests\Feature;

use App\Enum\CompanyType;
use App\Models\BusinessDivision;
use App\Models\Company;
use App\Models\Data\Timezone;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 * @group user
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
        $user = User::factory()->create();

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
                'sales_units' => [
                    '*' => ['id', 'unit_name',],
                ],
                'companies' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    }

    /**
     * Test can update an existing user.
     */
    public function testCanUpdateUser(): void
    {
        $this->authenticateApi();

        $user = User::factory()->create();

        $this->patchJson("api/users/".$user->getKey(), $data = [
            'first_name' => $firstName = preg_replace('/[^[:alpha:]]/', '', $this->faker->firstName),
            'middle_name' => $middleName = null,
            'last_name' => $lastName = preg_replace('/[^[:alpha:]]/', '', $this->faker->lastName),
            'timezone_id' => Timezone::query()->get()->random()->getKey(),
            'team_id' => $teamKey = UT_EPD_WW,
            'sales_units' => SalesUnit::query()->get()->random(2)->map(
                static fn(SalesUnit $unit) => ['id' => $unit->getKey()]
            ),
            'companies' => Company::factory()->count(2)->create(['type' => CompanyType::INTERNAL])->map->only('id'),
            'role_id' => Role::query()->get()->random()->getKey(),
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/users/'.$user->getKey())
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'team' => [
                    'id', 'team_name',
                ],
                'team_id',
                'sales_units' => [
                    '*' => ['id', 'unit_name',],
                ],
            ])
            ->assertJsonFragment([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'team_id' => $teamKey,
            ])
            ->assertJsonCount(count($data['sales_units']), 'sales_units')
            ->assertJsonCount(count($data['companies']), 'companies');

        foreach ($data['sales_units'] as $unit) {
            $this->assertContains($unit['id'], $response->json('sales_units.*.id'));
        }

        foreach ($data['companies'] as $company) {
            $this->assertContains($company['id'], $response->json('companies.*.id'));
        }
    }

    /**
     * Test an ability to activate an existing user.
     *
     * @return void
     */
    public function testCanActivateUser()
    {
        $this->authenticateApi();

        $user = User::factory()->create();
        $user->activated_at = null;
        $user->save();

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->putJson("api/users/activate/".$user->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at',
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

        $user = User::factory()->create();
        $user->activated_at = now();
        $user->save();

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));

        $this->putJson("api/users/deactivate/".$user->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/users/'.$user->getKey())
            ->assertJsonStructure([
                'id',
                'activated_at',
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

        $this->deleteJson("api/users/".$this->app['auth']->id())
//            ->dump()
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

        $user = User::factory()->create();

        $this->deleteJson("api/users/".$user->getKey())
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson('api/users/'.$user->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to view the data for user profile form.
     *
     * @return void
     */
    public function testCanViewDataForUserProfileForm()
    {
        $this->authenticateApi();

        $this->getJson('api/users/create')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'roles' => [
                    '*' => [
                        'id', 'name',
                    ],
                ],
                'countries' => [
                    '*' => [
                        'id', 'iso_3166_2',
                        'name', 'default_currency_id',
                        'user_id', 'is_system',
                        'currency_name', 'currency_symbol',
                        'currency_code', 'flag',
                        'created_at',
                        'updated_at',
                        'activated_at',
                    ],
                ],
                'timezones' => [
                    '*' => [
                        'id', 'text', 'value',
                    ],
                ],
            ]);
    }

    /**
     * Test an ability to filter list of users.
     */
    public function testCanFilterListOfUsers(): void
    {
        $this->authenticateApi();

        $userFromTeam = User::factory()->for(Team::factory())->create();

        $r = $this->getJson('api/users/list?'.Arr::query([
                'filter[team_id]' => $userFromTeam->team->getKey(),
            ]))
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'team_id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email',
                    'created_at',
                ],
            ]);

        $r->assertJsonCount(1);
        $this->assertContains($userFromTeam->getKey(), $r->json('*.id'));

        $userFromTeamWithBusinessDivision = User::factory()->for(Team::factory()->for(BusinessDivision::query()
            ->first()))->create();

        $r = $this->getJson('api/users/list?'.Arr::query([
                'filter[business_division_id]' => $userFromTeamWithBusinessDivision->team->businessDivision->getKey(),
            ]))
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'team_id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email',
                    'created_at',
                ],
            ]);

        $r->assertJsonCount(1);
        $this->assertContains($userFromTeamWithBusinessDivision->getKey(), $r->json('*.id'));

        $userFromCompany = User::factory()->hasAttached(Company::factory())->create();

        $r = $this->getJson('api/users/list?'.Arr::query([
                'filter[company_id]' => $userFromCompany->companies->first()->getKey(),
            ]))
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'team_id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email',
                    'created_at',
                ],
            ]);

        $r->assertJsonCount(1);
        $this->assertContains($userFromCompany->getKey(), $r->json('*.id'));
    }
}
