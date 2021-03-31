<?php

namespace App\Services;

use App\Contracts\{Services\ManagesExchangeRates, Services\Stats};
use App\DTO\AssetAggregate;
use App\Enum\QuoteStatus;
use App\Models\{Address,
    Asset,
    AssetTotal,
    Company,
    Customer\CustomerTotal,
    Location,
    Opportunity,
    OpportunityTotal,
    Quote\Quote,
    Quote\QuoteTotal,
    Quote\WorldwideQuote};
use App\Models\Quote\QuoteLocationTotal;
use App\Queries\AssetQueries;
use Illuminate\Database\{ConnectionInterface, Eloquent\Collection};
use Illuminate\Support\{Collection as BaseCollection,};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;
use Webpatser\Uuid\Uuid;

class StatsService implements Stats
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

    public function denormalizeSummaryOfOpportunities(): void
    {
        $this->progressStart(Opportunity::query()->count());

        $this->connection->transaction(function () {

            OpportunityTotal::query()->delete();

            Opportunity::query()
                ->chunkById(500, function (Collection $chunk) {

                    foreach ($chunk as $opportunity) {
                        $this->denormalizeSummaryOfOpportunity($opportunity);

                        $this->progressAdvance();
                    }

                });

        });

        $this->logger->info('Summary by Opportunities has been calculated.');

        $this->progressFinish();

    }

    protected function denormalizeSummaryOfOpportunity(Opportunity $opportunity): void
    {
        $baseOpportunityAmount = $opportunity->opportunity_amount * $this->resolveBaseRateOfOpportunityAmount($opportunity);

        tap(new OpportunityTotal(), function (OpportunityTotal $opportunityTotal) use ($baseOpportunityAmount, $opportunity) {
            $opportunityTotal->{$opportunityTotal->getKeyName()} = (string)Uuid::generate(4);
            $opportunityTotal->opportunity_id = $opportunity->getKey();

            $opportunityTotal->base_opportunity_amount = $baseOpportunityAmount;
            $opportunityTotal->opportunity_status = $opportunity->status;
            $opportunityTotal->opportunity_created_at = $opportunity->{$opportunity->getCreatedAtColumn()};

            $opportunityTotal->save();

            $countries = $opportunity->primaryAccount->countries()->distinct('countries.id')->pluck('countries.id')->all();

            $opportunityTotal->countries()->sync($countries);
        });
    }

    private function resolveBaseRateOfOpportunityAmount(Opportunity $opportunity): float
    {
        $currencyCode = $opportunity->opportunity_amount_currency_code;
        $currencyCode ??= 'GPB';

        return $this->exchangeRateService->getBaseRateByCurrencyCode($currencyCode, $opportunity->created_at);
    }

    public function denormalizeSummaryOfQuotes(): void
    {
        $this->progressStart(Quote::query()->count() + WorldwideQuote::query()->count());

        $this->connection->transaction(function () {
            /**
             * Truncate quote totals.
             */
            QuoteTotal::query()->delete();

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

    protected function denormalizeSummaryOfWorldwideQuote(WorldwideQuote $worldwideQuote): void
    {
        $opportunity = $worldwideQuote->opportunity;

        $primaryAccount = $opportunity->primaryAccount;

        if (is_null($primaryAccount)) {
            $this->logger->warning("Primary Account is not defined on Opportunity entity, Quote Number: '$worldwideQuote->quote_number'.");

            return;
        }

        if (is_null($worldwideQuote->quoteCurrency)) {
            $this->logger->warning("Quote Currency is not defined on WorldwideQuote entity, Quote Number: '$worldwideQuote->quote_number'.");

            return;
        }

        $priceSummary = $this->worldwideQuoteCalc->calculatePriceSummaryOfQuote(
            $worldwideQuote
        );

        $baseRateValue = $this->exchangeRateService->getBaseRate($worldwideQuote->quoteCurrency);

        $totalPriceOfQuote = $priceSummary->final_total_price * $baseRateValue;

        tap(new QuoteTotal(), function (QuoteTotal $quoteTotal) use ($opportunity, $primaryAccount, $totalPriceOfQuote, $worldwideQuote) {
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
            $quoteTotal->valid_until_date = $opportunity->opportunity_closing_date;
            $quoteTotal->quote_status = $worldwideQuote->status;

            $quoteTotal->save();

            $countries = $primaryAccount->countries()->pluck('countries.id')->all();

            $quoteTotal->countries()->sync($countries);
        });
    }

    protected function denormalizeSummaryOfRescueQuote(Quote $quote): void
    {
        $company = Company::query()
            ->where('name', $quote->customer->name)
            ->where(['type' => 'External', 'category' => 'End User'])
            ->first();

        if (!$company instanceof Company) {
            customlog(['message' => 'External Company does not exist']);

            return;
        }

        $version = $quote->activeVersionOrCurrent;

        $totalPrice = $version->totalPrice / $version->margin_divider * $version->base_exchange_rate;

        tap(new QuoteTotal(), function (QuoteTotal $quoteTotal) use ($totalPrice, $company, $quote) {
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
        $this->progressStart(QuoteTotal::query()->distinct('customer_name')->count());

        $this->connection->transaction(function () {
            /**
             * Truncate customer totals.
             */
            CustomerTotal::query()->delete();

            QuoteTotal::query()
                ->toBase()
                ->selectRaw('SUM(`total_price`) as `total_value`')
                ->selectRaw('COUNT(*) AS `total_count`')
                ->addSelect('company_id', 'customer_name', 'user_id')
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
            /**
             * Truncate quote location totals.
             */
            QuoteLocationTotal::query()->delete();

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
                (new QuoteLocationTotal([
                    'user_id' => $quoteTotal->user_id,
                    'location_id' => $location->getKey(),
                    'country_id' => $location->country->getKey(),
                    'location_coordinates' => $location->coordinates,
                    'location_address' => $location->formatted_address,
                    'total_drafted_value' => $quoteTotal->total_drafted_value,
                    'total_drafted_count' => $quoteTotal->total_drafted_count,
                    'total_submitted_value' => $quoteTotal->total_submitted_value,
                    'total_submitted_count' => $quoteTotal->total_submitted_count
                ]))
                    ->save();
            });
        });
    }

    protected function denormalizeSummaryOfAsset(Location $location): void
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
            ->where(['type' => 'External', 'category' => 'End User'])
            ->with('locations')
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
                /** @var CustomerTotal $customerTotal */
                $customerTotal = tap(new CustomerTotal(), function (CustomerTotal $customerTotal) use ($total, $address, $company) {
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
