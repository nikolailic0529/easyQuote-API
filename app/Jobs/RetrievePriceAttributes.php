<?php

namespace App\Jobs;

use App\Models\Quote\BaseQuote;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use DB;

class RetrievePriceAttributes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var \App\Models\Quote\BaseQuote */
    protected $quote;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BaseQuote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $priceList = $this->quote->priceList;

        if (!$priceList instanceof QuoteFile) {
            return;
        }

        $mapping = $this->quote->fieldsColumns()
            ->whereHas('templateField', function (Builder $query) {
                $query->whereIn('name', BaseQuote::PRICE_ATTRIBUTES_MAPPING);
            })
            ->with('templateField')
            ->get()
            ->pluck('importable_column_id', 'templateField.name');

        $importedColumns = $priceList->columnsData()->whereIn('importable_column_id', $mapping)
            ->toBase()
            ->select(['value', 'importable_column_id'])
            ->get()
            ->groupBy('importable_column_id');

        $attributes = $importedColumns->transform(function ($attributes) {
                return $attributes->pluck('value')->values()->filter()->toArray();
            })->keyBy(function ($attributes, $importableColumnId) use ($mapping) {
                $name = $mapping->flip()->get($importableColumnId);

                return array_flip(BaseQuote::PRICE_ATTRIBUTES_MAPPING)[$name];
            })->toArray();

        $attributes = array_merge_recursive($priceList->meta_attributes, $attributes);

        $attributes = array_map(function ($attribute) {
            return array_values(array_flip(array_flip($attribute)));
        }, $attributes);

        $priceList->storeMetaAttributes($attributes);

        $this->quote->fill($priceList->formatted_meta_attributes)->saveWithoutEvents();
    }
}
