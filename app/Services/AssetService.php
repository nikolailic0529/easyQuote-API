<?php

namespace App\Services;

use App\Contracts\{
    Services\QuoteState,
    Repositories\Quote\QuoteSubmittedRepositoryInterface as SubmittedQuotes,
};
use App\DTO\QuoteAsset;
use App\Models\{
    Address,
    Asset,
    AssetCategory,
    Quote\Quote,
};
use App\Models\Quote\BaseQuote;
use App\Services\Concerns\WithProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class AssetService
{
    use WithProgress;

    protected SubmittedQuotes $submittedQuotes;

    protected QuoteState $quoteState;

    protected Asset $asset;

    protected AssetCategory $assetCategory;

    protected array $assetCategoryCache = [];

    public function __construct(SubmittedQuotes $submittedQuotes, QuoteState $quoteState, Asset $asset, AssetCategory $assetCategory)
    {
        $this->submittedQuotes = $submittedQuotes;
        $this->quoteState = $quoteState;
        $this->asset = $asset;
        $this->assetCategory = $assetCategory;
    }

    public function migrateAssets(bool $fresh = false)
    {
        $bar = last(func_get_args());

        DB::transaction(function () use ($fresh, $bar) {
            if ($fresh) {
                $this->asset->whereIsMigrated(true)->forceDelete();
            }

            $this->setProgressBar($bar, fn () => $this->submittedQuotes->count($fresh ? [] : ['assets_migrated_at' => null]));

            $this->submittedQuotes
                ->cursor(fn (Builder $q) => $q->when(!$fresh, fn ($q) => $q->whereNull('assets_migrated_at')))
                ->each(fn (Quote $quote) => $this->migrateQuoteAssets($quote));
        });

        $this->finishProgress();
    }

    public function migrateQuoteAssets(Quote $quote)
    {
        try {
            /** @var \App\Collections\MappedRows */
            $rows = $this->quoteState->retrieveRows(
                $quote->usingVersion
            );

            DB::transaction(function () use ($quote, $rows) {
                $rows
                    ->filter(fn (object $row) => filled(optional($row)->product_no) && filled(optional($row)->serial_no))
                    ->each(fn (object $row) => $this->handleQuoteAsset($row, $quote->usingVersion));

                $quote->markAsMigrated('assets_migrated_at');
            });

            report_logger(
                ['message' => ASSET_MGQF_01],
                ['quote_id' => $quote->id, 'rfq_number' => $quote->rfq_number]
            );

            $this->advanceProgress();
        } catch (Throwable $e) {
            report_logger(
                ['ErrorCode' => 'ASSET_MGERR_01'],
                [
                    'ErrorDetails' => report_logger()->formatError(ASSET_MGERR_01, $e),
                    'quote_id' => $quote->id,
                    'rfq_number' => $quote->rfq_number
                ]
            );
        }
    }

    protected function handleQuoteAsset(object $row, BaseQuote $quote): Asset
    {
        $assetCategory = $this->recognizeAssetCategory($row);
        $assetAddress = $this->getAssetAddress($quote, $assetCategory);

        $quoteAsset = QuoteAsset::create($row, $quote, $assetCategory, $assetAddress);

        $asset = $this->asset->firstOrCreate(
            $quoteAsset->only(
                'user_id',
                'asset_category_id',
                'vendor_id',
                'address_id',
                'item_number',
                'product_number',
                'serial_number',
                'product_description',
                'service_description',
                'base_warranty_start_date',
                'base_warranty_end_date',
                'active_warranty_start_date',
                'active_warranty_end_date',
                'unit_price',
                'buy_price',
            )->toArray(),
            $quoteAsset->toArray()
        );

        return tap(
            $asset,
            fn (Asset $asset) => $asset->wasRecentlyCreated
                ? report_logger(['message' => ASSET_MGSS_01], $asset->toArray())
                : report_logger(['message' => ASSET_MGAE_01], $asset->toArray())
        );
    }

    protected function getAssetCategory(string $name): ?AssetCategory
    {
        if (isset($this->assetCategoryCache[$name])) {
            return $this->assetCategoryCache[$name];
        }

        return $this->assetCategoryCache[$name] = $this->assetCategory->whereName($name)->first();
    }

    protected function recognizeAssetCategory(object $row)
    {
        if (optional($row)->price == 0) {
            return $this->getAssetCategory('Software');
        }

        return $this->getAssetCategory('Hardware');
    }

    protected function getAssetAddress(BaseQuote $quote, ?AssetCategory $assetCategory): ?Address
    {
        if (optional($assetCategory)->name === 'Software') {
            return $quote->customer->addresses->firstWhere('address_type', 'Software') ?? $quote->customer->addresses->first();
        }

        return $quote->customer->addresses->firstWhere('address_type', 'Hardware') ?? $quote->customer->addresses->first();
    }
}
