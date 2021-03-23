<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test an ability to view paginated Teams.
     *
     * @return void
     */
    public function testCanViewPaginatedTeams()
    {
        $this->authenticateApi();

        $this->getJson('api/teams')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'team_name', 'monthly_goal_amount', 'is_system', 'created_at'
                    ]
                ]
            ]);

        $this->getJson('api/teams?order_by_created_at=asc');
        $this->getJson('api/teams?order_by_team_name=asc');
        $this->getJson('api/teams?order_by_monthly_goal_amount=asc');
    }

    /**
     * Test an ability to view list of the existing Teams.
     *
     * @return void
     */
    public function testCanViewListOfTeams()
    {
        $this->authenticateApi();

        $this->getJson('api/teams/list')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'team_name'
                    ]
                ]
            ]);
    }

    /**
     * Test an ability to create a new Team.
     *
     * @return void
     */
    public function testCanCreateTeam()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/teams', [
            'team_name' => $teamName = $this->faker->text(191),
            'monthly_goal_amount' => $monthlyGoalAmount = $this->faker->randomFloat(0, 0, 999999999)
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'team_name',
                'monthly_goal_amount',
                'created_at',
                'updated_at'
            ]);

        $modelKey = $response->json('id');

        $this->getJson('api/teams/'.$modelKey)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'team_name',
                'monthly_goal_amount',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'team_name' => $teamName,
                'monthly_goal_amount' => $monthlyGoalAmount
            ]);
    }

    /**
     * Test an ability to update an existing Team.
     *
     * @return void
     */
    public function testCanUpdateTeam()
    {
        $this->authenticateApi();

        $team = factory(Team::class)->create();

        $this->patchJson('api/teams/'.$team->getKey(), [
            'team_name' => $teamName = $this->faker->text(191),
            'monthly_goal_amount' => $monthlyGoalAmount = $this->faker->randomFloat(0, 999999999)
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'team_name',
                'monthly_goal_amount',
                'created_at',
                'updated_at'
            ]);

        $this->getJson('api/teams/'.$team->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'team_name',
                'monthly_goal_amount',
                'created_at',
                'updated_at'
            ])
            ->assertJson([
                'team_name' => $teamName,
                'monthly_goal_amount' => $monthlyGoalAmount
            ]);
    }

    /**
     * Test an ability to delete an existing Team.
     *
     * @return void
     */
    public function testCanDeleteTeam()
    {
        $this->authenticateApi();

        $team = factory(Team::class)->create();

        $this->deleteJson('api/teams/'.$team->getKey())
            ->assertNoContent();

        $this->getJson('api/teams/'.$team->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to delete an existing system defined team.
     *
     * @return void
     */
    public function testCanNotDeleteSystemDefinedTeam()
    {
        $this->authenticateApi();

        $team = factory(Team::class)->create(['is_system' => true]);

        $this->deleteJson('api/teams/'.$team->getKey())
            ->assertForbidden();

        $this->getJson('api/teams/'.$team->getKey())
            ->assertOk();
    }

}
