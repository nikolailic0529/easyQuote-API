<?php

namespace App\Services;

use App\Contracts\{
    Services\Stats,
    Services\QuoteServiceInterface as QuoteService,
    Repositories\Customer\CustomerRepositoryInterface as Customers,
    Repositories\Quote\QuoteDraftedRepositoryInterface as DraftedQuotes,
    Repositories\Quote\QuoteSubmittedRepositoryInterface as SubmittedQuotes,
    Repositories\AssetRepository as Assets,
};
use App\DTO\AssetAggregate;
use App\Models\{
    Address,
    Asset,
    AssetTotal,
    Company,
    Quote\Quote,
    Quote\QuoteTotal,
    Customer\CustomerTotal,
    Location,
};
use App\Models\Quote\QuoteLocationTotal;
use App\Services\Concerns\WithProgress;
use Illuminate\Database\{
    Eloquent\Builder,
    Eloquent\Collection as DbCollection,
};
use Illuminate\Database\Query\Builder as DbBuilder;
use Illuminate\Support\{
    Collection,
    Facades\DB,
};
use Throwable;

class StatsService implements Stats
{
    use WithProgress;

    protected QuoteTotal $quoteTotal;

    protected QuoteLocationTotal $quoteLocationTotal;

    protected CustomerTotal $customerTotal;

    protected AssetTotal $assetTotal;

    protected QuoteService $quoteService;

    protected DraftedQuotes $draftedQuotes;

    protected SubmittedQuotes $submittedQuotes;

    protected Assets $assets;

    protected Customers $customers;

    public function __construct(
        QuoteTotal $quoteTotal,
        QuoteLocationTotal $quoteLocationTotal,
        CustomerTotal $customerTotal,
        AssetTotal $assetTotal,
        QuoteService $quoteService,
        DraftedQuotes $draftedQuotes,
        SubmittedQuotes $submittedQuotes,
        Assets $assets,
        Customers $customers
    ) {
        $this->quoteTotal = $quoteTotal;
        $this->quoteLocationTotal = $quoteLocationTotal;
        $this->customerTotal = $customerTotal;
        $this->assetTotal = $assetTotal;
        $this->assets = $assets;
        $this->quoteService = $quoteService;
        $this->draftedQuotes = $draftedQuotes;
        $this->submittedQuotes = $submittedQuotes;
        $this->customers = $customers;
    }

    public function calculateQuoteTotals(): void
    {
        $this->setProgressBar(head(func_get_args()), fn () => $this->draftedQuotes->count() + $this->submittedQuotes->count());

        DB::transaction(function () {
            /**
             * Truncate quote totals.
             */
            $this->quoteTotal->query()->delete();

            $this->draftedQuotes->cursor(
                fn (Builder $q) => $q->with('countryMargin', 'usingVersion')
                    ->has('customer.equipmentLocation')
            )
                ->each(fn (Quote $quote) => $this->handleQuote($quote));

            $this->submittedQuotes->cursor(
                fn (Builder $q) => $q->with('countryMargin', 'usingVersion')
                    ->has('customer.equipmentLocation')
            )
                ->each(fn (Quote $quote) => $this->handleQuote($quote));
        });

        $this->finishProgress();
    }

    public function calculateCustomerTotals(): void
    {
        $this->setProgressBar(head(func_get_args()), fn () => $this->quoteTotal->query()->distinct('customer_name')->count());

        DB::transaction(function () {
            /**
             * Truncate customer totals.
             */
            $this->customerTotal->query()->delete();

            $this->quoteTotal->query()
                ->toBase()
                ->selectRaw('SUM(`total_price`) as `total_value`')
                ->selectRaw('COUNT(*) AS `total_count`')
                ->addSelect('company_id', 'customer_name', 'user_id')
                ->groupBy('company_id', 'user_id')
                ->orderBy('company_id')
                ->chunk(100, fn (Collection $chunk) => $chunk->each(fn (object $total) => $this->handleCustomerTotal($total)));
        });

        $this->finishProgress();
    }

    public function calculateQuoteLocationTotals(): void
    {
        $this->setProgressBar(last(func_get_args()), fn () => $this->quoteTotal->query()->distinct('location_id')->count());

        DB::transaction(function () {
            /**
             * Truncate quote location totals.
             */
            $this->quoteLocationTotal->query()->delete();

            $this->quoteTotal->on(MYSQL_UNBUFFERED)
                ->with('location')
                ->groupByRaw('location_id')
                ->cursor()
                ->each(fn (QuoteTotal $quoteTotal) => $this->handleQuoteLocation($quoteTotal->location));
        });
    }

    public function calculateAssetTotals(): void
    {
        $this->setProgressBar(head(func_get_args()), fn () => $this->assets->count());

        DB::transaction(function () {
            /**
             * Truncate asset totals.
             */
            $this->assetTotal->query()->delete();

            $this->assets->locationsQuery()
                ->orderBy('locations.id')
                ->chunk(20, fn (DbCollection $chunk) => $chunk->each(fn (Asset $asset) => $this->handleAssetLocation($asset->location)));
        });

        $this->finishProgress();
    }

    protected function handleQuoteLocation(Location $location): void
    {
        $quoteTotals = $this->quoteTotal->query()
            ->selectRaw('SUM(CASE WHEN ISNULL(`quote_submitted_at`) THEN `total_price` ELSE 0 END) AS `total_drafted_value`')
            ->selectRaw('COUNT(ISNULL(`quote_submitted_at`) OR NULL) AS `total_drafted_count`')
            ->selectRaw('SUM(CASE WHEN `quote_submitted_at` IS NOT NULL THEN `total_price` ELSE 0 END) AS `total_submitted_value`')
            ->selectRaw('COUNT(`quote_submitted_at` IS NOT NULL OR NULL) AS `total_submitted_count`')
            ->addSelect('user_id')
            ->whereLocationId($location->getKey())
            ->groupBy('user_id')
            ->get();

        /**
         * We are creating aggregates for each quote user.
         */
        DB::transaction(
            fn () =>
            $quoteTotals->each(
                fn (QuoteTotal $quoteTotal) => $this->quoteLocationTotal->make([
                    'user_id' => $quoteTotal->user_id,
                    'location_id' => $location->id,
                    'country_id' => $location->country->getKey(),
                    'location_coordinates' => $location->coordinates,
                    'location_address' => $location->formatted_address,
                    'total_drafted_value' => $quoteTotal->total_drafted_value,
                    'total_drafted_count' => $quoteTotal->total_drafted_count,
                    'total_submitted_value' => $quoteTotal->total_submitted_value,
                    'total_submitted_count' => $quoteTotal->total_submitted_count
                ])
                    ->saveOrFail()
            ),
            5
        );

        $this->advanceProgress();
    }

    protected function handleAssetLocation(Location $location): void
    {
        $totals = $this->assets->aggregatesByUserAndLocation($location->getKey());

        /**
         * We are creating aggregates for each asset user.
         */
        DB::transaction(
            fn () =>
            $totals->each(
                fn (AssetAggregate $aggregate) => $this->assetTotal->make([
                    'location_id' => $location->id,
                    'country_id' => $location->country->getKey(),
                    'user_id' => $aggregate->user_id,
                    'location_coordinates' => $location->coordinates,
                    'location_address' => $location->formatted_address,
                    'total_count' => $aggregate->total_count,
                    'total_value' => $aggregate->total_value
                ])
                    ->save()
            )
        );

        $this->advanceProgress($totals->sum('total_count'));
    }

    protected function handleQuote(Quote $quote): bool
    {
        try {
            $company = Company::whereName($quote->customer->name)->where(['type' => 'External', 'category' => 'End User'])->first();

            if (!$company instanceof Company) {
                customlog(['message' => 'External Company does not exist']);

                return false;
            }

            $version = $quote->usingVersion;

            $totalPrice = $version->totalPrice / $version->margin_divider * $version->base_exchange_rate;

            $attributes = [
                'quote_id'              => $quote->id,
                'customer_id'           => $quote->customer_id,
                'company_id'            => $company->id,
                'location_id'           => $quote->customer->equipmentLocation->id,
                'country_id'            => $quote->customer->equipmentLocation->country->getKey(),
                'user_id'               => $quote->user_id,
                'location_address'      => $quote->customer->equipmentLocation->formatted_address,
                'location_coordinates'  => $quote->customer->equipmentLocation->coordinates,
                'total_price'           => $totalPrice,
                'customer_name'         => $quote->customer->name,
                'rfq_number'            => $quote->customer->rfq,
                'quote_created_at'      => $quote->getRawOriginal('created_at'),
                'quote_submitted_at'    => $quote->getRawOriginal('submitted_at'),
                'valid_until_date'      => $quote->customer->getRawOriginal('valid_until'),
            ];

            $this->quoteTotal->query()->make($attributes)->save();

            customlog(['message' => sprintf(QSC_01, $quote->customer->rfq, $totalPrice)]);

            $this->advanceProgress();

            return true;
        } catch (Throwable $e) {
            customlog(['ErrorCode' => 'QSC_ERR_01'], sprintf('%s. Details: %s', QSC_ERR_01, $e->getMessage()));

            return false;
        }
    }

    /**
     * Find external company with exact name.
     * Retrieve company locations.
     * Create or Update customer totals for each location.
     *
     * @param object $total
     * @return void
     */
    protected function handleCustomerTotal(object $total): void
    {
        $company = Company::whereKey($total->company_id)->where(['type' => 'External', 'category' => 'End User'])->with('locations')->first();

        if (!$company instanceof Company) {
            customlog(['message' => 'External Company does not exist']);

            return;
        }

        /** Eager load addresses having location */
        $company->load(['addresses' => fn ($q) => $q->has('location'), 'addresses.location']);

        if ($company->addresses->isEmpty()) {
            customlog(['message' => 'External Company does not have any location']);
            return;
        }

        DB::transaction(
            fn () =>
            $company->addresses->each(function (Address $address) use ($company, $total) {
                $customerTotal = tap($this->customerTotal->query()->make(
                    [
                        'customer_name' => $company->name,
                        'location_id' => $address->location_id,
                        'country_id' => $address->location->country->getKey(),
                        'address_id' => $address->id,
                        'company_id' => $company->id,
                        'user_id' => $total->user_id,
                        'total_count' => $total->total_count,
                        'total_value' => $total->total_value,
                        'location_address' => $address->location->formatted_address,
                        'location_coordinates' => $address->location->coordinates,
                    ]
                ))->save();
                
                customlog(['message' => 'A new customer total created with new location'], $customerTotal->toArray());
            }),
            5
        );

        $this->advanceProgress();
    }
}
