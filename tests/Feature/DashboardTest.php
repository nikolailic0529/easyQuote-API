<?php

namespace Tests\Feature;

use App\Enum\QuoteStatus;
use App\Models\Data\Country;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteTotal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

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
                'opportunities_count',
                'opportunities_value',
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
            $this->assertIsNumeric($response->json('totals.opportunities_count'));
            $this->assertIsNumeric($response->json('totals.opportunities_value'));
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
        $user = User::factory()->create();

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
                'opportunities_count',
                'opportunities_value',
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
            $this->assertIsNumeric($response->json('totals.opportunities_count'));
            $this->assertIsNumeric($response->json('totals.opportunities_value'));
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

    public function testCanViewStatsOfUserOwnEntities()
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions([
            'view_own_quote_contracts',
            'view_own_quotes',
            'download_sales_order_pdf',
            'update_own_external_quotes',
            'update_own_internal_quotes',
            'create_external_quotes',
            'create_contracts',
            'view_own_internal_quotes',
            'delete_own_quote_contracts',
            'delete_own_external_quotes',
            'download_hpe_contract_pdf',
            'download_ww_quote_payment_schedule',
            'view_own_hpe_contracts',
            'download_ww_quote_pdf',
            'download_quote_schedule',
            'create_quote_files',
            'cancel_sales_orders',
            'download_quote_price',
            'download_quote_pdf',
            'delete_quote_files',
            'delete_own_internal_quotes',
            'update_quote_files',
            'handle_quote_files',
            'create_internal_quotes',
            'update_own_quotes',
            'download_ww_quote_distributor_file',
            'download_contract_pdf',
            'view_own_contracts',
            'create_quote_contracts',
            'create_quotes',
            'view_own_external_quotes',
            'update_own_quote_contracts',
            'update_own_contracts',
            'delete_own_contracts',
            'update_own_hpe_contracts',
            'create_hpe_contracts',
            'delete_own_quotes',
            'view_quote_files',
            'delete_own_hpe_contracts']);

        /** @var User $user */
        $user = User::factory()->create();

        $user->syncRoles($role);

        $this->actingAs($user, 'api');

        /** @var Quote $quote */
        $quote = factory(Quote::class)->create([
            'user_id' => $user->getKey(),
            'submitted_at' => null,
        ]);

        $quoteTotal = tap(new QuoteTotal(), function (QuoteTotal $quoteTotal) use ($quote) {
            $quoteTotal->quote()->associate($quote);
            $quoteTotal->customer_id = $quote->customer_id;
            $quoteTotal->company_id = $quote->company_id;
            $quoteTotal->user_id = $quote->user_id;
            $quoteTotal->customer_name = $quote->customer->name;
            $quoteTotal->rfq_number = $quote->customer->rfq;
            $quoteTotal->quote_created_at = $quote->created_at;
            $quoteTotal->quote_submitted_at = null;
            $quoteTotal->quote_status = QuoteStatus::ALIVE;
            $quoteTotal->total_price = 1000.00;

            $quoteTotal->save();
        });

        $response = $this->getJson('api/stats')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
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
                    'lost_opportunities_value',
                    'lost_opportunities_count',
                    'lost_opportunities_value'
                ],
                'period' => [
                    'start_date', 'end_date'
                ],
                'base_currency'
            ]);

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
            $this->assertIsNumeric($response->json('totals.opportunities_count'));
            $this->assertIsNumeric($response->json('totals.opportunities_value'));
        };

        $validateTotalsFromResponse($response);

        $this->assertSame(1, $response->json('totals.drafted_quotes_count'));
        $this->assertSame(1000, $response->json('totals.drafted_quotes_value'));
    }
}
