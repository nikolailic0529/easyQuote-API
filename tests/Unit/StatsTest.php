<?php

namespace Tests\Unit;

use App\Contracts\Services\LocationService;
use App\DTO\Stats\SummaryRequestData;
use App\DTO\Summary;
use App\Models\{Data\Country, Quote\Quote, Quote\WorldwideQuote};
use App\Services\Stats\StatsAggregationService;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Unit\Traits\{WithFakeUser};

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
        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $quotes = $aggregator->getQuoteTotalLocations($this->faker->uuid);

        $this->assertInstanceOf(Collection::class, $quotes);
    }

    /**
     * Test Quotes on Map aggregate.
     *
     * @return void
     */
    public function testQuotesOnMap()
    {
        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var LocationService */
        $locationService = $this->app->make(LocationService::class);

        $quotes = $aggregator->getQuoteLocations(
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
        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var LocationService */
        $locationService = $this->app->make(LocationService::class);

        $assets = $aggregator->getAssetTotalLocations(
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
        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var LocationService */
        $locationService = $this->app->make(LocationService::class);

        $customers = $aggregator->getCustomerTotalLocations(
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
     * @throws BindingResolutionException
     */
    public function testCustomersSummary()
    {
        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $classMap = array_flip(Relation::$morphMap);

        $entityTypes = [
            $classMap[Quote::class],
            $classMap[WorldwideQuote::class]
        ];

        $customers = $aggregator->getCustomersSummary(new SummaryRequestData([
                'country_id' => Country::query()->value('id'),
                'entity_types' => $entityTypes
            ])
        );

        $this->assertIsObject($customers);
        $this->assertObjectHasAttribute('customers_count', $customers);
    }

    /**
     * Test Quotes Summary aggregate.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testQuotesSummary()
    {
        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $period = CarbonPeriod::create(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $classMap = array_flip(Relation::$morphMap);

        $entityTypes = [
            $classMap[Quote::class],
            $classMap[WorldwideQuote::class]
        ];

        /** @var Summary */
        $quotes = $aggregator->getQuotesSummary(new SummaryRequestData([
            'period' => $period,
            'entity_types' => $entityTypes
        ]));

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
