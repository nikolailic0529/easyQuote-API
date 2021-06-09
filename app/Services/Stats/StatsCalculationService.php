<?php

namespace App\Services\Stats;

use App\Contracts\{Services\ManagesExchangeRates, Services\Stats};
use App\DTO\AssetAggregate;
use App\Enum\QuoteStatus;
use App\Models\{Address,
    Asset,
    AssetTotal,
    Company,
    Customer\CustomerTotal,
    Location,
    Quote\Quote,
    Quote\QuoteTotal,
    Quote\WorldwideQuote};
use App\Models\Quote\QuoteLocationTotal;
use App\Queries\AssetQueries;
use App\Services\WorldwideQuote\WorldwideQuoteCalc;
use Illuminate\Database\{ConnectionInterface, Eloquent\Collection, Query\JoinClause};
use Illuminate\Support\{Collection as BaseCollection,};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;

class StatsCalculationService implements Stats
{
    protected OutputInterface $output;

    protected ConnectionInterface $connection;

    protected LoggerInterface $logger;

    protected AssetQueries $assetQueries;

    protected WorldwideQuoteCalc $worldwideQuoteCalc;

    protected ManagesExchangeRates $exchangeRateService;

    public function __construct(ConnectionInterface $connection,
                                LoggerInterface $logger,
                                AssetQueries $assetQueries,
                                WorldwideQuoteCalc $worldwideQuoteCalc,
                                ManagesExchangeRates $exchangeRateService,
                                OutputInterface $output = null)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->assetQueries = $assetQueries;
        $this->worldwideQuoteCalc = $worldwideQuoteCalc;
        $this->exchangeRateService = $exchangeRateService;
        $this->output = $output ?? new NullOutput();
    }

    public function setOutput(OutputInterface $output): Stats
    {
        return tap($this, function () use ($output) {
            $this->output = $output;
        });
    }

    public function denormalizeSummaryOfQuotes(): void
    {
        $this->progressStart(Quote::query()->count() + WorldwideQuote::query()->count());

        $this->connection->transaction(function () {
            /**
             * Truncate quote totals.
             */
            QuoteTotal::query()
                ->leftJoin('quotes', function (JoinClause $join) {
                    $join->on('quotes.id', 'quote_totals.quote_id')
                        ->whereNull('quotes.deleted_at');
                })
                ->leftJoin('worldwide_quotes', function (JoinClause $join) {
                    $join->on('worldwide_quotes.id', 'quote_totals.quote_id')
                        ->whereNull('worldwide_quotes.deleted_at');
                })
                ->whereNull(['quotes.id', 'worldwide_quotes.id'])
                ->delete();

            Quote::query()
                ->with('customer.equipmentLocation')
                ->chunkById(500, function (Collection $chunk) {
                    foreach ($chunk as $quote) {
                        $this->denormalizeSummaryOfRescueQuote($quote);
                        $this->progressAdvance();
                    }
                });

            WorldwideQuote::query()
                ->with('opportunity.primaryAccount')
                ->chunkById(500, function (Collection $chunk) {
                    foreach ($chunk as $quote) {
                        $this->denormalizeSummaryOfWorldwideQuote($quote);
                        $this->progressAdvance();
                    }
                });
        });

        $this->logger->info('Summary by Rescue & Worldwide Quotes has been calculated.');

        $this->progressFinish();
    }

    public function denormalizeSummaryOfWorldwideQuote(WorldwideQuote $worldwideQuote): void
    {
        $opportunity = $worldwideQuote->opportunity;

        $primaryAccount = $opportunity->primaryAccount;

        if (is_null($primaryAccount)) {
            $this->logger->warning("Primary Account is not defined on Opportunity entity, Quote Number: '$worldwideQuote->quote_number'.");

            return;
        }

        if (is_null($worldwideQuote->activeVersion->quoteCurrency)) {
            $this->logger->warning("Quote Currency is not defined on WorldwideQuote entity, Quote Number: '$worldwideQuote->quote_number'.");

            return;
        }

        $priceSummary = $this->worldwideQuoteCalc->calculatePriceSummaryOfQuote(
            $worldwideQuote
        );

        $baseRateValue = $this->exchangeRateService->getBaseRate($worldwideQuote->activeVersion->quoteCurrency);

        $totalPriceOfQuote = $priceSummary->final_total_price * $baseRateValue;

        $quoteTotal = QuoteTotal::query()
            ->where('quote_id', $worldwideQuote->getKey())
            ->firstOrNew();

        tap($quoteTotal, function (QuoteTotal $quoteTotal) use ($opportunity, $primaryAccount, $totalPriceOfQuote, $worldwideQuote) {
            $quoteTotal->quote()->associate($worldwideQuote);
            $quoteTotal->customer_id = null;
            $quoteTotal->company_id = $primaryAccount->getKey();
            $quoteTotal->user_id = $worldwideQuote->user_id;

            $quoteTotal->location_id = null; // TODO: determine the location entity key
            $quoteTotal->location_address = null; // TODO: determine the machine address
            $quoteTotal->location_coordinates = null; // TODO: determine coordinates of machine address
            $quoteTotal->total_price = $totalPriceOfQuote;
            $quoteTotal->customer_name = $primaryAccount->name;
            $quoteTotal->rfq_number = $worldwideQuote->quote_number;
            $quoteTotal->quote_created_at = $worldwideQuote->{$worldwideQuote->getCreatedAtColumn()};
            $quoteTotal->quote_submitted_at = $worldwideQuote->submitted_at;
            $quoteTotal->valid_until_date = $worldwideQuote->activeVersion->quote_expiry_date;
            $quoteTotal->quote_status = $worldwideQuote->status ?? QuoteStatus::ALIVE;

            $quoteTotal->save();

            $countries = $primaryAccount->countries()->pluck('countries.id')->all();

            $quoteTotal->countries()->sync($countries);
        });
    }

    public function denormalizeSummaryOfRescueQuote(Quote $quote): void
    {
        $company = Company::query()
            ->where('name', $quote->customer->name)
            ->where(['type' => 'External', 'category' => 'End User'])
            ->first();

        if (!$company instanceof Company) {
            customlog(['message' => "External Company does not exist. Company Name: '{$quote->customer->name}'."]);

            return;
        }

        $version = $quote->activeVersionOrCurrent;

        $totalPrice = $version->totalPrice / $version->margin_divider * $version->base_exchange_rate;

        $quoteTotal = QuoteTotal::query()
            ->where('quote_id', $quote->getKey())
            ->firstOrNew();

        tap($quoteTotal, function (QuoteTotal $quoteTotal) use ($totalPrice, $company, $quote) {
            $quoteTotal->quote()->associate($quote);
            $quoteTotal->customer_id = $quote->customer_id;
            $quoteTotal->company_id = $company->getKey();
            $quoteTotal->location_id = $quote->customer->equipmentLocation->id;

            $quoteTotal->user_id = $quote->user_id;

            $quoteTotal->location_address = $quote->customer->equipmentLocation->formatted_address;
            $quoteTotal->location_coordinates = $quote->customer->equipmentLocation->coordinates;
            $quoteTotal->total_price = $totalPrice;
            $quoteTotal->customer_name = $quote->customer->name;
            $quoteTotal->rfq_number = $quote->customer->rfq;
            $quoteTotal->quote_created_at = $quote->getRawOriginal('created_at');
            $quoteTotal->quote_submitted_at = $quote->getRawOriginal('submitted_at');
            $quoteTotal->valid_until_date = $quote->customer->getRawOriginal('valid_until');
            $quoteTotal->quote_status = QuoteStatus::ALIVE;

            $quoteTotal->save();

            $countries = $quote->customer->addresses()
                ->whereNotNull('country_id')
                ->distinct('country_id')
                ->pluck('country_id')
                ->all();

            $quoteTotal->countries()->sync($countries);
        });
    }

    public function denormalizeSummaryOfCustomers(): void
    {
        $this->progressStart();

        $this->connection->transaction(function () {

            QuoteTotal::query()
                ->toBase()
                ->selectRaw('SUM(`total_price`) as `total_value`')
                ->selectRaw('COUNT(*) AS `total_count`')
                ->addSelect('company_id', 'user_id')
                ->groupBy('company_id', 'user_id')
                ->orderBy('company_id')
                ->chunk(100, function (BaseCollection $chunk) {
                    foreach ($chunk as $total) {
                        $this->denormalizeSummaryOfCustomer($total);
                        $this->progressAdvance();
                    }
                });

        });

        $this->logger->info('Summary by Customers has been calculated.');

        $this->progressFinish();
    }

    public function denormalizeSummaryOfLocations(): void
    {
        $this->progressStart(QuoteTotal::query()->distinct('location_id')->count());

        $this->connection->transaction(function () {
            QuoteTotal::query()
                ->with('location')
                ->has('location')
                ->groupByRaw('location_id')
                ->chunk(500, function (Collection $chunk) {
                    foreach ($chunk as $quoteTotal) {
                        $this->denormalizeSummaryOfLocation($quoteTotal->location);
                        $this->progressAdvance();
                    }

                });
        });

        $this->logger->info('Summary by Locations of Quote entity has been calculated.');

        $this->progressFinish();
    }

    public function denormalizeSummaryOfAssets(): void
    {
        $this->progressStart(Asset::query()->count());

        $this->connection->transaction(function () {
            /**
             * Truncate asset totals.
             */
            AssetTotal::query()->delete();

            $this->assetQueries
                ->locationsQuery()
                ->orderBy('locations.id')
                ->chunk(500, function (Collection $chunk) {
                    foreach ($chunk as $asset) {
                        /** @var Asset $asset */
                        $this->denormalizeSummaryOfAsset($asset->location);
                        $this->progressAdvance();
                    }
                });
        });

        $this->logger->info('Summary by Assets has been calculated.');

        $this->progressFinish();
    }

    protected function denormalizeSummaryOfLocation(Location $location): void
    {
        $quoteTotals = QuoteTotal::query()
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) THEN `total_price` ELSE 0 END) AS `total_drafted_value`')
            ->selectRaw('COUNT(ISNULL(`quote_submitted_at`) OR NULL) AS `total_drafted_count`')
            ->selectRaw('SUM(CASE WHEN `quote_submitted_at` IS NOT NULL THEN `total_price` ELSE 0 END) AS `total_submitted_value`')
            ->selectRaw('COUNT(`quote_submitted_at` IS NOT NULL OR NULL) AS `total_submitted_count`')
            ->addSelect('user_id')
            ->where('location_id', $location->getKey())
            ->groupBy('user_id')
            ->get();

        /**
         * We are creating aggregates for each quote user.
         */
        $this->connection->transaction(function () use ($quoteTotals, $location) {
            $quoteTotals->each(function (QuoteTotal $quoteTotal) use ($location) {

                $quoteLocationTotal = QuoteLocationTotal::query()
                    ->where('quote_total_id', $quoteTotal->getKey())
                    ->where('location_id', $location->getKey())
                    ->where('user_id', $quoteTotal->user_id)
                    ->firstOrNew();

                tap($quoteLocationTotal, function (QuoteLocationTotal $quoteLocationTotal) use ($location, $quoteTotal) {
                    $quoteLocationTotal->quote_total_id = $quoteTotal->getKey();
                    $quoteLocationTotal->user_id = $quoteTotal->user_id;
                    $quoteLocationTotal->location_id = $location->getKey();
                    $quoteLocationTotal->country_id = $location->country->getKey();
                    $quoteLocationTotal->location_coordinates = $location->coordinates;
                    $quoteLocationTotal->location_address = $location->formatted_address;
                    $quoteLocationTotal->total_drafted_value = $quoteTotal->total_drafted_value;
                    $quoteLocationTotal->total_drafted_count = $quoteTotal->total_drafted_count;
                    $quoteLocationTotal->total_submitted_value = $quoteTotal->total_submitted_value;
                    $quoteLocationTotal->total_submitted_count = $quoteTotal->total_submitted_count;

                    $quoteTotal->save();
                });

            });
        });
    }

    public function denormalizeSummaryOfAsset(Location $location): void
    {
        $aggregatedAssetData = $this->assetQueries
            ->aggregateByUserAndLocationQuery($location->getKey())
            ->get()
            ->mapInto(AssetAggregate::class);

        /**
         * We are creating aggregates for each asset user.
         */
        $this->connection->transaction(function () use ($aggregatedAssetData, $location) {

            $aggregatedAssetData->each(function (AssetAggregate $aggregate) use ($location) {

                tap(new AssetTotal(), function (AssetTotal $assetTotal) use ($location, $aggregate) {
                    $assetTotal->location_id = $location->getKey();
                    $assetTotal->country_id = $location->country->getKey();
                    $assetTotal->user_id = $aggregate->user_id;
                    $assetTotal->location_coordinates = $location->coordinates;
                    $assetTotal->location_address = $location->formatted_address;
                    $assetTotal->total_count = $aggregate->total_count;
                    $assetTotal->total_value = $aggregate->total_value;

                    $assetTotal->save();
                });
            });

        });
    }

    /**
     * Find external company with exact name.
     * Retrieve company locations.
     * Create or Update customer totals for each location.
     *
     * @param object $total
     * @return void
     * @throws Throwable
     */
    protected function denormalizeSummaryOfCustomer(object $total): void
    {
        $company = Company::query()
            ->whereKey($total->company_id)
            ->first();

        if (!$company instanceof Company) {
            $this->logger->warning("Unable to find the company entity, ID: {$total->company_id}.");

            return;
        }

        /** Eager load addresses having location */
        $company->load(['addresses' => function ($q) {
            $q->has('location');
        }, 'addresses.location']);

        if ($company->addresses->isEmpty()) {
            $this->logger->warning("The Company entity does not have any location, ID: {$company->getKey()}.");

            return;
        }

        $this->connection->transaction(function () use ($company, $total) {

            $company->addresses->each(function (Address $address) use ($company, $total) {

                $customerTotal = CustomerTotal::query()
                    ->where('company_id', $company->getKey())
                    ->where('user_id', $total->user_id)
                    ->where('location_id', $address->location_id)
                    ->firstOrNew();

                tap($customerTotal, function (CustomerTotal $customerTotal) use ($total, $address, $company) {
                    $customerTotal->customer_name = $company->name;
                    $customerTotal->location_id = $address->location_id;
                    $customerTotal->country_id = $address->location->country->getKey();
                    $customerTotal->address_id = $address->getKey();
                    $customerTotal->company_id = $company->getKey();
                    $customerTotal->user_id = $total->user_id;
                    $customerTotal->total_count = $total->total_count;
                    $customerTotal->total_value = $total->total_value;
                    $customerTotal->location_address = $address->location->formatted_address;
                    $customerTotal->location_coordinates = $address->location->coordinates;

                    $customerTotal->save();
                });

                $this->logger->info('A new customer total created with new location', $customerTotal->toArray());
            });
        });
    }

    /**
     * Starts the progress output.
     * @param int $max
     */
    private function progressStart(int $max = 0): void
    {
        if ($this->output instanceof StyleInterface) {
            $this->output->progressStart($max);
        }
    }

    /**
     * Advances the progress output X steps.
     * @param int $step
     */
    private function progressAdvance(int $step = 1): void
    {
        if ($this->output instanceof StyleInterface) {
            $this->output->progressAdvance($step);
        }
    }

    /**
     * Finishes the progress output.
     */
    private function progressFinish(): void
    {
        if ($this->output instanceof StyleInterface) {
            $this->output->progressFinish();
        }
    }
}
