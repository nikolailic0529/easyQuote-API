<?php

namespace App\Jobs;

use App\Enum\Lock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Database\{
    Query\Builder as QueryBuilder,
    Query\JoinClause
};
use Illuminate\Support\Facades\DB;
use App\Models\{
    Quote\BaseQuote,
    QuoteFile\QuoteFile
};
use App\Repositories\Concerns\ManagesSchemalessAttributes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class RetrievePriceAttributes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ManagesSchemalessAttributes;

    protected BaseQuote $quote;

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
            ->join(
                'template_fields',
                fn (JoinClause $join) =>
                $join->on('template_fields.id', '=', $this->quote->fieldsColumns()->getQuery()->qualifyColumn('template_field_id'))
                    ->whereIn('template_fields.name', BaseQuote::PRICE_ATTRIBUTES_MAPPING)
            )
            ->toBase()
            ->pluck('importable_column_id', 'template_fields.name');

        $subQuery = $priceList->rowsData()->toBase()
            ->whereNotNull('columns_data')
            ->when(true, function (QueryBuilder $query) use ($mapping) {
                $mapping->each(fn ($id, $column) => $this->unpivotJsonColumn($query, 'columns_data', "$.\"{$id}\".value", $column));
            });

        $importedColumns = DB::query()->fromSub($subQuery, 'columns')->groupBy(...$mapping->keys())->get();

        $attributes = [];

        $mapping->keys()->each(function ($column) use (&$attributes, $importedColumns) {
            $attribute = data_get(array_flip(BaseQuote::PRICE_ATTRIBUTES_MAPPING), $column);
            $values = $importedColumns->pluck($column)->reject(fn ($value) => blank($value) || $value === 'null')->toArray();

            data_set($attributes, $attribute, $values);
        });

        $attributes = array_filter(array_merge_recursive($priceList->meta_attributes, $attributes), fn ($value) => is_array($value));

        $attributes = array_map(fn ($attribute) => array_values(
            array_flip(
                array_flip(array_filter($attribute))
            )
        ), $attributes);

        $fileLock = Cache::lock(Lock::UPDATE_QUOTE_FILE($priceList->getKey()), 10);
        $quoteLock = Cache::lock(Lock::UPDATE_QUOTE($this->quote->getKey()), 10);

        $quoteLock->block(60, function () use ($attributes) {
            $quoteAttributes = [
                'pricing_document' => implode(', ', Arr::get($attributes, 'pricing_document')),
                'system_handle' => implode(', ', Arr::get($attributes, 'system_handle')),
                'service_agreement_id' => implode(', ', Arr::get($attributes, 'service_agreement_id')),
            ];

            $this->quote->whereKey($this->quote->getKey())->toBase()->update($quoteAttributes);
        });

        $fileLock->block(60, fn () => $priceList->forceFill(['meta_attributes' => json_encode($attributes)])->save());
    }
}
