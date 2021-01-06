<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

/**
 * @group build
 */
class DashboardTest extends TestCase
{
    use WithFakeUser;

    /**
     * Test Dashboard Stats Request.
     *
     * @return void
     */
    public function testStatsRetrieving()
    {
        $responseStructure = [
            'totals' => [
                'drafted_quotes_count',
                'submitted_quotes_count',
                'drafted_quotes_value',
                'submitted_quotes_value',
                'expiring_quotes_count',
                'expiring_quotes_value',
                'customers_count',
                'locations_total',
            ],
            'period' => [
                'start_date', 'end_date'
            ],
            'base_currency'
        ];

        $response = $this->getJson(url('api/stats'))->assertOk()->assertJsonStructure($responseStructure);

        $this->assertEquals(null, $response->json('period.start_date'));
        $this->assertEquals(null, $response->json('period.end_date'));
        $this->assertIsNumeric($response->json('totals.drafted_quotes_count'));
        $this->assertIsNumeric($response->json('totals.submitted_quotes_count'));
        $this->assertIsNumeric($response->json('totals.expiring_quotes_count'));
        $this->assertIsNumeric($response->json('totals.drafted_quotes_value'));
        $this->assertIsNumeric($response->json('totals.submitted_quotes_value'));
        $this->assertIsNumeric($response->json('totals.expiring_quotes_value'));
        $this->assertIsNumeric($response->json('totals.customers_count'));
        $this->assertIsNumeric($response->json('totals.locations_total'));
        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));

        $startDate = now()->subMonth()->toDateString();
        $endDate = now()->toDateString();

        $response = $this->postJson(url('api/stats'), ['start_date' => $startDate, 'end_date' => $endDate])->assertJsonStructure($responseStructure);

        $this->assertIsNumeric($response->json('totals.drafted_quotes_count'));
        $this->assertIsNumeric($response->json('totals.submitted_quotes_count'));
        $this->assertIsNumeric($response->json('totals.expiring_quotes_count'));
        $this->assertIsNumeric($response->json('totals.drafted_quotes_value'));
        $this->assertIsNumeric($response->json('totals.submitted_quotes_value'));
        $this->assertIsNumeric($response->json('totals.expiring_quotes_value'));
        $this->assertIsNumeric($response->json('totals.customers_count'));
        $this->assertIsNumeric($response->json('totals.locations_total'));
        $this->assertEquals($startDate, $response->json('period.start_date'));
        $this->assertEquals($endDate, $response->json('period.end_date'));
        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));
    }
}
