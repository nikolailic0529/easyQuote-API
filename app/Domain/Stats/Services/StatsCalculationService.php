<?php

namespace App\Domain\Stats\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Queries\AssetQueries;
use App\Domain\Company\Enum\CompanyCategoryEnum;
use App\Domain\Company\Models\Company;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;
use App\Domain\Location\Models\Location;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Services\RescueQuoteCalc;
use App\Domain\Stats\Contracts\Stats;
use App\Domain\Stats\DataTransferObjects\AssetAggregate;
use App\Domain\Stats\Models\AssetTotal;
use App\Domain\Stats\Models\CustomerTotal;
use App\Domain\Stats\Models\QuoteLocationTotal;
use App\Domain\Stats\Models\QuoteTotal;
use App\Domain\Worldwide\Enum\QuoteStatus;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\{Collection as BaseCollection};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

class StatsCalculationService implements \App\Domain\Stats\Contracts\Stats
{
    protected OutputInterface $output;

    public function __construct(protected LoggerInterface $logger,
                                protected AssetQueries $assetQueries,
                                protected RescueQuoteCalc $rescueQuoteCalc,
                                protected WorldwideQuoteCalc $worldwideQuoteCalc,
                                protected ManagesExchangeRates $exchangeRateService,
                                OutputInterface $output = null)
    {
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

        /*
         * Truncate quote totals with deleted quote parents.
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
            ->get()
            ->each(function (QuoteTotal $quoteTotal) {
                $quoteTotal->delete();
            });

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

        tap($quoteTotal, function (QuoteTotal $quoteTotal) use ($primaryAccount, $totalPriceOfQuote, $worldwideQuote) {
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
            ->where(['type' => 'External'])
            ->whereRelation('categories', 'name', '=', CompanyCategoryEnum::EndUser)
            ->first();

        if (!$company instanceof Company) {
            return;
        }

        $priceSummary = $this->rescueQuoteCalc->calculatePriceSummaryOfRescueQuote($quote);

        $baseRateValue = $this->exchangeRateService->getBaseRate($quote->activeVersionOrCurrent->targetCurrency);

        $totalPrice = $priceSummary->final_total_price * $baseRateValue;

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

        $this->logger->info('Summary by Customers has been calculated.');

        $this->progressFinish();
    }

    public function denormalizeSummaryOfLocations(): void
    {
        $this->progressStart(QuoteTotal::query()->distinct('location_id')->count());

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

        $this->logger->info('Summary by Locations of Quote entity has been calculated.');

        $this->progressFinish();
    }

    public function denormalizeSummaryOfAssets(): void
    {
        $this->progressStart(Asset::query()->count());

        /*
         * Truncate asset totals.
         */
        AssetTotal::query()->delete();

        $this->assetQueries
            ->locationsQuery()
            ->orderBy('locations.id')
            ->chunk(500, function (Collection $chunk) {
                foreach ($chunk as $asset) {
                    /* @var Asset $asset */
                    $this->denormalizeSummaryOfAsset($asset->location);
                    $this->progressAdvance();
                }
            });

        $this->logger->info('Summary by Assets has been calculated.');

        $this->progressFinish();
    }

    protected function denormalizeSummaryOfLocation(Location $location): void
    {
        $totals = QuoteTotal::query()
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) THEN `total_price` ELSE 0 END) AS `total_drafted_value`')
            ->selectRaw('COUNT(ISNULL(`quote_submitted_at`) OR NULL) AS `total_drafted_count`')
            ->selectRaw('SUM(CASE WHEN `quote_submitted_at` IS NOT NULL THEN `total_price` ELSE 0 END) AS `total_submitted_value`')
            ->selectRaw('COUNT(`quote_submitted_at` IS NOT NULL OR NULL) AS `total_submitted_count`')
            ->whereBelongsTo($location)
            ->toBase()
            ->first();

        $quoteLocationTotal = QuoteLocationTotal::query()
            ->whereBelongsTo($location)
            ->firstOrNew();

        tap($quoteLocationTotal ?? new QuoteLocationTotal(), function (QuoteLocationTotal $quoteLocationTotal) use ($location, $totals) {
            $quoteLocationTotal->location()->associate($location);
            $quoteLocationTotal->country()->associate($location->country);
            $quoteLocationTotal->location_coordinates = $location->coordinates;
            $quoteLocationTotal->location_address = $location->formatted_address;
            $quoteLocationTotal->total_drafted_value = $totals->total_drafted_value;
            $quoteLocationTotal->total_drafted_count = $totals->total_drafted_count;
            $quoteLocationTotal->total_submitted_value = $totals->total_submitted_value;
            $quoteLocationTotal->total_submitted_count = $totals->total_submitted_count;

            $quoteLocationTotal->save();
        });
    }

    public function denormalizeSummaryOfAsset(Location $location): void
    {
        $aggregatedAssetData = $this->assetQueries
            ->aggregateByUserAndLocationQuery($location->getKey())
            ->get()
            ->mapInto(AssetAggregate::class);

        /*
         * We are creating aggregates for each asset user.
         */
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
    }

    /**
     * Find external company with exact name.
     * Retrieve company locations.
     * Create or Update customer totals for each location.
     *
     * @throws \Throwable
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

        /* Eager load addresses having location */
        $company->load(['addresses' => function ($q) {
            $q->has('location');
        }, 'addresses.location']);

        if ($company->addresses->isEmpty()) {
            $this->logger->warning("The Company entity does not have any location, ID: {$company->getKey()}.");

            return;
        }

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
        });
    }

    /**
     * Starts the progress output.
     */
    private function progressStart(int $max = 0): void
    {
        if ($this->output instanceof StyleInterface) {
            $this->output->progressStart($max);
        }
    }

    /**
     * Advances the progress output X steps.
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
