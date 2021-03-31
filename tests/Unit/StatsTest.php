<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser
};
use App\Contracts\Services\LocationService;
use App\Contracts\Services\Stats;
use App\DTO\Summary;
use App\Models\{Quote\Quote, Quote\QuoteTotal, Data\Country, Quote\WorldwideQuote};
use App\Services\StatsAggregator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group build
 */
class StatsTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

//    /**
//     * Test stats calculation.
//     *
//     * @return void
//     */
//    public function testStatsCalculation()
//    {
//        app(Stats::class)->denormalizeSummaryOfQuotes();
//
//        $this->assertDatabaseCount('quote_totals', Quote::query()->count() + WorldwideQuote::query()->count());
//    }

    /**
     * Test Quotes Stats by Location.
     *
     * @return void
     */
    public function testQuotesStatsByLocation()
    {
        /** @var StatsAggregator */
        $aggregator = app(StatsAggregator::class);

        $quotes = $aggregator->quotesByLocation($this->faker->uuid);

        $this->assertInstanceOf(Collection::class, $quotes);
    }

    /**
     * Test Quotes on Map aggregate.
     *
     * @return void
     */
    public function testQuotesOnMap()
    {
        /** @var StatsAggregator */
        $aggregator = app(StatsAggregator::class);

        /** @var LocationService */
        $locationService = app(LocationService::class);

        $quotes = $aggregator->mapQuoteTotals(
            $locationService->renderPoint(
                $this->faker->latitude,
                $this->faker->longitude
            ),
            $locationService->renderPolygon(
                $this->faker->latitude,
                $this->faker->longitude,
                $this->faker->latitude,
                $this->faker->longitude
            )
        );

        $this->assertInstanceOf(Collection::class, $quotes);
    }

    /**
     * Test Assets on Map aggregate.
     *
     * @return void
     */
    public function testAssetsOnMap()
    {
        /** @var StatsAggregator */
        $aggregator = app(StatsAggregator::class);

        /** @var LocationService */
        $locationService = app(LocationService::class);

        $assets = $aggregator->mapAssetTotals(
            $locationService->renderPoint(
                $this->faker->latitude,
                $this->faker->longitude
            ),
            $locationService->renderPolygon(
                $this->faker->latitude,
                $this->faker->longitude,
                $this->faker->latitude,
                $this->faker->longitude
            ),
            null
        );

        $this->assertInstanceOf(Collection::class, $assets);

    }

    /**
     * Test Customers on Map aggregate.
     *
     * @return void
     */
    public function testCustomersOnMap()
    {
        /** @var StatsAggregator */
        $aggregator = app(StatsAggregator::class);

        /** @var LocationService */
        $locationService = app(LocationService::class);

        $customers = $aggregator->mapCustomerTotals(
            $locationService->renderPolygon(
                $this->faker->latitude,
                $this->faker->longitude,
                $this->faker->latitude,
                $this->faker->longitude
            ),
            null
        );

        $this->assertInstanceOf(Collection::class, $customers);
    }

    /**
     * Test Customers Summary aggregate.
     *
     * @return void
     */
    public function testCustomersSummary()
    {
        /** @var StatsAggregator */
        $aggregator = app(StatsAggregator::class);

        $customers = $aggregator->customersSummary(
            value($this->faker->randomElement([null, fn () => Country::value('id')]))
        );

        $this->assertIsObject($customers);
        $this->assertObjectHasAttribute('customers_count', $customers);
    }

    /**
     * Test Quotes Summary aggregate.
     *
     * @return void
     */
    public function testQuotesSummary()
    {
        /** @var StatsAggregator */
        $aggregator = app(StatsAggregator::class);

        $period = CarbonPeriod::create(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        /** @var Summary */
        $quotes = $aggregator->quotesSummary($period);

        $this->assertInstanceOf(Summary::class, $quotes);

        $this->assertIsArray($quotes->period);
        $this->assertIsArray($quotes->totals);

        $this->assertArrayHasKey('start_date', $quotes->period);
        $this->assertArrayHasKey('end_date', $quotes->period);
        $this->assertArrayHasKey('drafted_quotes_count', $quotes->totals);
        $this->assertArrayHasKey('submitted_quotes_count', $quotes->totals);
        $this->assertArrayHasKey('expiring_quotes_count', $quotes->totals);
        $this->assertArrayHasKey('drafted_quotes_value', $quotes->totals);
        $this->assertArrayHasKey('submitted_quotes_value', $quotes->totals);
        $this->assertArrayHasKey('expiring_quotes_value', $quotes->totals);
        $this->assertArrayHasKey('customers_count', $quotes->totals);
        $this->assertArrayHasKey('locations_total', $quotes->totals);

        $this->assertEquals($period->getStartDate()->toDateString(), $quotes->period['start_date']);
        $this->assertEquals($period->getEndDate()->toDateString(), $quotes->period['end_date']);

        $this->assertIsNumeric($quotes->totals['drafted_quotes_count']);
        $this->assertIsNumeric($quotes->totals['submitted_quotes_count']);
        $this->assertIsNumeric($quotes->totals['expiring_quotes_count']);
        $this->assertIsNumeric($quotes->totals['drafted_quotes_value']);
        $this->assertIsNumeric($quotes->totals['submitted_quotes_value']);
        $this->assertIsNumeric($quotes->totals['expiring_quotes_value']);
        $this->assertIsNumeric($quotes->totals['customers_count']);
        $this->assertIsNumeric($quotes->totals['locations_total']);

        $this->assertEquals(setting('base_currency'), $quotes->base_currency);
    }
}
