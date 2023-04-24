<?php

namespace Tests\Unit;

use App\Domain\Country\Models\Country;
use App\Domain\Geocoding\Contracts\AddressGeocoder;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Stats\DataTransferObjects\Summary;
use App\Domain\Stats\DataTransferObjects\SummaryRequestData;
use App\Domain\Stats\Services\StatsAggregationService;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Carbon\CarbonPeriod;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * @group build
 */
class StatsTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

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
        $this->authenticateApi();

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
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var \App\Domain\Geocoding\Contracts\AddressGeocoder $locationService */
        $locationService = $this->app->make(AddressGeocoder::class);

        $quotes = $aggregator->getQuoteLocations(
            new Point(
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
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var \App\Domain\Geocoding\Contracts\AddressGeocoder $locationService */
        $locationService = $this->app->make(AddressGeocoder::class);

        $assets = $aggregator->getAssetTotalLocations(
            new Point(
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
        $this->authenticateApi();

        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var \App\Domain\Geocoding\Contracts\AddressGeocoder */
        $locationService = $this->app->make(AddressGeocoder::class);

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
     *
     * @throws BindingResolutionException
     */
    public function testAggregatesSummaryOfCustomers()
    {
        $this->authenticateApi();

        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $classMap = array_flip(Relation::$morphMap);

        $entityTypes = [
            $classMap[Quote::class],
            $classMap[WorldwideQuote::class],
        ];

        $customers = $aggregator->getCustomersSummary(new SummaryRequestData([
                'country_id' => Country::query()->value('id'),
                'entity_types' => $entityTypes,
                'any_owner_entities' => true,
                'acting_user_led_teams' => [],
            ])
        );

        $this->assertIsObject($customers);
        $this->assertTrue(property_exists($customers, 'customers_count'));
    }

    /**
     * Test Quotes Summary aggregate.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function testAggregatesSummaryOfQuotes()
    {
        $this->authenticateApi();

        /** @var StatsAggregationService */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $period = CarbonPeriod::create(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $classMap = array_flip(Relation::$morphMap);

        $entityTypes = [
            $classMap[Quote::class],
            $classMap[WorldwideQuote::class],
        ];

        /** @var Summary */
        $quotes = $aggregator->getQuotesSummary(new SummaryRequestData([
            'period' => $period,
            'entity_types' => $entityTypes,
            'any_owner_entities' => true,
            'acting_user_led_teams' => [],
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
