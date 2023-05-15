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

    /**
     * Test it aggregates a concrete quote location.
     */
    public function testQuotesStatsByLocation(): void
    {
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $quotes = $aggregator->getQuoteTotalLocations($this->faker->uuid);

        $this->assertInstanceOf(Collection::class, $quotes);
    }

    /**
     * Test it aggregates quote locations.
     */
    public function testQuotesOnMap(): void
    {
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var AddressGeocoder $locationService */
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
     * Test it aggregates asset locations.
     *
     * @return void
     */
    public function testAssetsOnMap(): void
    {
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var AddressGeocoder $locationService */
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
     * Test it aggregates customer locations.
     *
     * @return void
     */
    public function testCustomersOnMap(): void
    {
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        /** @var AddressGeocoder $locationService */
        $locationService = $this->app->make(AddressGeocoder::class);

        $customers = $aggregator->getCustomerTotalLocations(
            $locationService->renderPolygon(
                $this->faker->latitude,
                $this->faker->longitude,
                $this->faker->latitude,
                $this->faker->longitude
            )
        );

        $this->assertInstanceOf(Collection::class, $customers);
    }

    /**
     * Test it aggregates summary of customers.
     */
    public function testAggregatesSummaryOfCustomers(): void
    {
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $country = Country::query()->firstOrFail();

        $customers = $aggregator->getCustomersSummary(new SummaryRequestData(
            userId: $this->app['auth.driver']->id(),
            countryId: $country->getKey()
        ));

        $this->assertIsObject($customers);
        $this->assertTrue(property_exists($customers, 'customers_count'));
    }

    /**
     * Test it aggregates summary of quotes.
     */
    public function testAggregatesSummaryOfQuotes(): void
    {
        $this->authenticateApi();

        /** @var StatsAggregationService $aggregator */
        $aggregator = $this->app->make(StatsAggregationService::class);

        $period = CarbonPeriod::create(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $quotes = $aggregator->getQuotesSummary(new SummaryRequestData(
            userId: $this->app['auth.driver']->id(),
            period: $period,
        ));

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
