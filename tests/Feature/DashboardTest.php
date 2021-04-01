<?php

namespace Tests\Feature;

use App\Models\Data\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class DashboardTest extends TestCase
{
    /**
     * Test can view dashboard summary acting as super user.
     *
     * @return void
     */
    public function testCanViewStatsAsSuperUser()
    {
        $this->authenticateApi();

        $responseStructure = [
            'totals' => [
                'drafted_quotes_count',
                'submitted_quotes_count',
                'drafted_quotes_value',
                'submitted_quotes_value',
                'expiring_quotes_count',
                'expiring_quotes_value',
                'submitted_sales_orders_count',
                'drafted_sales_orders_count',
                'dead_quotes_count',
                'customers_count',
                'locations_total',
                'lost_opportunities_count',
                'lost_opportunities_value'
            ],
            'period' => [
                'start_date', 'end_date'
            ],
            'base_currency'
        ];

        $response = $this->getJson('api/stats')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure($responseStructure);

        $validateTotalsFromResponse = function (TestResponse $response) {
            $this->assertIsNumeric($response->json('totals.drafted_quotes_count'));
            $this->assertIsNumeric($response->json('totals.submitted_quotes_count'));
            $this->assertIsNumeric($response->json('totals.expiring_quotes_count'));
            $this->assertIsNumeric($response->json('totals.drafted_quotes_value'));
            $this->assertIsNumeric($response->json('totals.submitted_quotes_value'));
            $this->assertIsNumeric($response->json('totals.expiring_quotes_value'));
            $this->assertIsNumeric($response->json('totals.customers_count'));
            $this->assertIsNumeric($response->json('totals.locations_total'));
            $this->assertIsNumeric($response->json('totals.submitted_sales_orders_count'));
            $this->assertIsNumeric($response->json('totals.drafted_sales_orders_count'));
            $this->assertIsNumeric($response->json('totals.lost_opportunities_count'));
            $this->assertIsNumeric($response->json('totals.dead_quotes_count'));
            $this->assertIsNumeric($response->json('totals.dead_quotes_value'));
            $this->assertIsNumeric($response->json('totals.lost_opportunities_count'));
            $this->assertIsNumeric($response->json('totals.lost_opportunities_value'));
        };

        $this->assertEquals(null, $response->json('period.start_date'));
        $this->assertEquals(null, $response->json('period.end_date'));
        $validateTotalsFromResponse($response);

        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));

        $startDate = now()->subMonth()->toDateString();
        $endDate = now()->toDateString();

        $response = $this->postJson('api/stats', ['start_date' => $startDate, 'end_date' => $endDate])
            ->assertOk()
            ->assertJsonStructure($responseStructure);

        $validateTotalsFromResponse($response);

        $this->assertEquals($startDate, $response->json('period.start_date'));
        $this->assertEquals($endDate, $response->json('period.end_date'));
        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));

        $response = $this->postJson('api/stats', ['country_id' => Country::query()->value('id')])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure($responseStructure);

        $validateTotalsFromResponse($response);
    }

    /**
     * Test an ability to view dashboard summary as acting as sales manager.
     *
     * @return void
     */
    public function testCanViewStatsAsSalesManager()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();
        /** @var User $user */
        $user = factory(User::class)->create();

        $user->syncRoles($role);

        $this->authenticateApi($user);

        $responseStructure = [
            'totals' => [
                'drafted_quotes_count',
                'submitted_quotes_count',
                'drafted_quotes_value',
                'submitted_quotes_value',
                'expiring_quotes_count',
                'expiring_quotes_value',
                'submitted_sales_orders_count',
                'drafted_sales_orders_count',
                'dead_quotes_count',
                'customers_count',
                'locations_total',
                'lost_opportunities_count',
                'lost_opportunities_value'
            ],
            'period' => [
                'start_date', 'end_date'
            ],
            'base_currency'
        ];

        $response = $this->getJson('api/stats')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure($responseStructure);

        $validateTotalsFromResponse = function (TestResponse $response) {
            $this->assertIsNumeric($response->json('totals.drafted_quotes_count'));
            $this->assertIsNumeric($response->json('totals.submitted_quotes_count'));
            $this->assertIsNumeric($response->json('totals.expiring_quotes_count'));
            $this->assertIsNumeric($response->json('totals.drafted_quotes_value'));
            $this->assertIsNumeric($response->json('totals.submitted_quotes_value'));
            $this->assertIsNumeric($response->json('totals.expiring_quotes_value'));
            $this->assertIsNumeric($response->json('totals.customers_count'));
            $this->assertIsNumeric($response->json('totals.locations_total'));
            $this->assertIsNumeric($response->json('totals.submitted_sales_orders_count'));
            $this->assertIsNumeric($response->json('totals.drafted_sales_orders_count'));
            $this->assertIsNumeric($response->json('totals.lost_opportunities_count'));
            $this->assertIsNumeric($response->json('totals.dead_quotes_count'));
            $this->assertIsNumeric($response->json('totals.dead_quotes_value'));
            $this->assertIsNumeric($response->json('totals.lost_opportunities_count'));
            $this->assertIsNumeric($response->json('totals.lost_opportunities_value'));
        };

        $this->assertEquals(null, $response->json('period.start_date'));
        $this->assertEquals(null, $response->json('period.end_date'));
        $validateTotalsFromResponse($response);

        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));

        $startDate = now()->subMonth()->toDateString();
        $endDate = now()->toDateString();

        $response = $this->postJson('api/stats', ['start_date' => $startDate, 'end_date' => $endDate])
            ->assertOk()
            ->assertJsonStructure($responseStructure);

        $validateTotalsFromResponse($response);

        $this->assertEquals($startDate, $response->json('period.start_date'));
        $this->assertEquals($endDate, $response->json('period.end_date'));
        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));

        $response = $this->postJson('api/stats', ['country_id' => Country::query()->value('id')])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure($responseStructure);

        $validateTotalsFromResponse($response);


    }
}
