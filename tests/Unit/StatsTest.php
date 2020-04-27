<?php

namespace Tests\Unit;

use App\Models\{
    Quote\Quote,
    Quote\QuoteTotal
};
use App\Services\StatsService;
use Tests\TestCase;
use Tests\Unit\Traits\{
    TruncatesDatabaseTables,
    WithFakeUser
};

class StatsTest extends TestCase
{
    use WithFakeUser, TruncatesDatabaseTables;

    protected array $truncatableTables = ['quote_totals'];

    /**
     * Test stats calculation.
     *
     * @return void
     */
    public function testStatsCalculation()
    {
        app(StatsService::class)->calculateQuotesTotals();

        $this->assertEquals(QuoteTotal::count(), Quote::count());
    }

    /**
     * Test stats retrieving.
     * Test that response data is valid.
     *
     * @return void
     */
    public function testStatsRetrieving()
    {
        $responseStructure = [
            'totals' => [
                'quotes' => [
                    'drafted', 'submitted', 'expiring'
                ]
            ],
            'period' => [
                'start_date', 'end_date'
            ],
            'base_currency'
        ];

        $response = $this->getJson(url('api/stats'))->assertOk()
            ->assertJsonStructure($responseStructure);

        $this->assertEquals(null, $response->json('period.start_date'));
        $this->assertEquals(null, $response->json('period.end_date'));
        $this->assertIsNumeric($response->json('totals.quotes.drafted'));
        $this->assertIsNumeric($response->json('totals.quotes.submitted'));
        $this->assertIsNumeric($response->json('totals.quotes.expiring'));
        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));

        $startDate = now()->subMonth()->toDateString();
        $endDate = now()->toDateString();

        $response = $this->postJson(url('api/stats'), ['start_date' => $startDate, 'end_date' => $endDate])
            ->assertJsonStructure($responseStructure);

        $this->assertIsNumeric($response->json('totals.quotes.drafted'));
        $this->assertIsNumeric($response->json('totals.quotes.submitted'));
        $this->assertIsNumeric($response->json('totals.quotes.expiring'));
        $this->assertEquals($startDate, $response->json('period.start_date'));
        $this->assertEquals($endDate, $response->json('period.end_date'));
        $this->assertEquals(setting('base_currency'), $response->json('base_currency'));
    }
}
