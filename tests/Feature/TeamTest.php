<?php

namespace Tests\Feature;

use App\Models\SalesUnit;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test an ability to view paginated Teams.
     */
    public function testCanViewPaginatedTeams(): void
    {
        $this->authenticateApi();

        $this->getJson('api/teams')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'team_name', 'monthly_goal_amount', 'is_system', 'created_at',
                    ],
                ],
            ]);

        $this->getJson('api/teams?order_by_created_at=asc');
        $this->getJson('api/teams?order_by_team_name=asc');
        $this->getJson('api/teams?order_by_monthly_goal_amount=asc');
    }

    /**
     * Test an ability to view list of the existing Teams.
     */
    public function testCanViewListOfTeams(): void
    {
        $this->authenticateApi();

        $this->getJson('api/teams/list')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'team_name',
                    ],
                ],
            ]);
    }

    /**
     * Test an ability to create a new Team.
     */
    public function testCanCreateTeam(): void
    {
        $this->authenticateApi();

        $response = $this->postJson('api/teams', $data = [
            'team_name' => $teamName = $this->faker->text(100),
            'monthly_goal_amount' => $monthlyGoalAmount = $this->faker->randomFloat(0, 0, 999999999),
            'business_division_id' => BD_RESCUE,
            'team_leaders' => User::factory()->count(2)->create()->map->only('id'),
            'sales_units' => SalesUnit::factory()->count(2)->create()->map->only('id'),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'team_name',
                'monthly_goal_amount',
                'created_at',
                'updated_at',
            ]);

        $modelKey = $response->json('id');

        $r = $this->getJson('api/teams/'.$modelKey)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'business_division_id',
                'team_name',
                'monthly_goal_amount',
                'team_leaders' => [
                    '*' => [
                        'id', 'first_name', 'last_name',
                    ],
                ],
                'sales_units' => [
                    '*' => [
                        'id',
                        'unit_name',
                    ],
                ],
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'team_name' => $teamName,
                'monthly_goal_amount' => $monthlyGoalAmount,
            ])
            ->assertJsonCount(count($data['team_leaders']), 'team_leaders')
            ->assertJsonCount(count($data['sales_units']), 'sales_units');

        foreach ($data['team_leaders'] as $item) {
            $this->assertContains($item['id'], $r->json('team_leaders.*.id'));
        }

        foreach ($data['sales_units'] as $item) {
            $this->assertContains($item['id'], $r->json('sales_units.*.id'));
        }
    }

    /**
     * Test an ability to update an existing Team.
     */
    public function testCanUpdateTeam(): void
    {
        $this->authenticateApi();

        $team = factory(Team::class)->create();

        $this->patchJson('api/teams/'.$team->getKey(), $data = [
            'team_name' => $teamName = $this->faker->text(100),
            'monthly_goal_amount' => $monthlyGoalAmount = $this->faker->randomFloat(0, 999999999),
            'business_division_id' => BD_RESCUE,
            'team_leaders' => User::factory()->count(2)->create()->map->only('id'),
            'sales_units' => SalesUnit::factory()->count(2)->create()->map->only('id'),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'team_name',
                'monthly_goal_amount',
                'created_at',
                'updated_at',
            ]);

        $r = $this->getJson('api/teams/'.$team->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'business_division_id',
                'team_name',
                'monthly_goal_amount',
                'team_leaders' => [
                    '*' => [
                        'id', 'first_name', 'last_name',
                    ],
                ],
                'sales_units' => [
                    '*' => [
                        'id',
                        'unit_name',
                    ],
                ],
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'team_name' => $teamName,
                'monthly_goal_amount' => $monthlyGoalAmount,
            ])
            ->assertJsonCount(count($data['team_leaders']), 'team_leaders')
            ->assertJsonCount(count($data['sales_units']), 'sales_units');

        foreach ($data['team_leaders'] as $item) {
            $this->assertContains($item['id'], $r->json('team_leaders.*.id'));
        }

        foreach ($data['sales_units'] as $item) {
            $this->assertContains($item['id'], $r->json('sales_units.*.id'));
        }
    }

    /**
     * Test an ability to delete an existing Team.
     */
    public function testCanDeleteTeam(): void
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
     */
    public function testCanNotDeleteSystemDefinedTeam(): void
    {
        $this->authenticateApi();

        $team = Team::factory()->create(['is_system' => true]);

        $this->deleteJson('api/teams/'.$team->getKey())
            ->assertForbidden();

        $this->getJson('api/teams/'.$team->getKey())
            ->assertOk();
    }

}
