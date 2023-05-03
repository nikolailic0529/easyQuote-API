<?php

namespace App\Domain\Rescue\Jobs;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Concerns\ManagesSchemalessAttributes;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Sync\Enum\Lock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RetrievePriceAttributes implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use ManagesSchemalessAttributes;

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
                fn (JoinClause $join) => $join->on('template_fields.id', '=', $this->quote->fieldsColumns()->getQuery()->qualifyColumn('template_field_id'))
                    ->whereIn('template_fields.name', BaseQuote::PRICE_ATTRIBUTES_MAPPING)
            )
            ->toBase()
            ->pluck('importable_column_id', 'template_fields.name');

        if ($mapping->isEmpty()) {
            return;
        }

        $subQuery = $priceList->rowsData()->toBase()
            ->whereNotNull('columns_data')
            ->when(true, function (QueryBuilder $query) use ($mapping) {
                $mapping->each(fn ($id, $column) => $this->unpivotJsonColumn($query, 'columns_data', "$.\"{$id}\".value", $column));
            });

        /** @var BaseCollection $importedColumns */
        $importedColumns = DB::query()->fromSub($subQuery, 'columns')->groupBy(...$mapping->keys())->get();

        $newAttributes = [];

        $priceAttributeDictionary = array_flip(BaseQuote::PRICE_ATTRIBUTES_MAPPING);

        foreach ($mapping->keys() as $column) {
            if (!isset($priceAttributeDictionary[$column])) {
                continue;
            }

            $attribute = $priceAttributeDictionary[$column];

            $values = $importedColumns->pluck($column)
                ->reject(fn ($value) => blank($value) || $value === 'null')
                ->all();

            $newAttributes[$attribute] = $values;
        }

        $attributes = array_filter(array_merge_recursive(
            $priceList->meta_attributes ?? [],
            $newAttributes
        ), 'is_array');

        $attributes = array_map(fn (array $attributeValues) => array_values(array_unique($attributeValues)), $attributes);

        $fileLock = Cache::lock(Lock::UPDATE_QUOTE_FILE($priceList->getKey()), 10);
        $quoteLock = Cache::lock(Lock::UPDATE_QUOTE($this->quote->getKey()), 10);

        $quoteLock->block(60, function () use ($attributes) {
            $quoteAttributes = [
                'pricing_document' => implode(', ', Arr::wrap(Arr::get($attributes, 'pricing_document'))),
                'system_handle' => implode(', ', Arr::wrap(Arr::get($attributes, 'system_handle'))),
                'service_agreement_id' => implode(', ', Arr::wrap(Arr::get($attributes, 'service_agreement_id'))),
            ];

            $this->quote->whereKey($this->quote->getKey())->toBase()->update($quoteAttributes);
        });

        $fileLock->block(60, fn () => $priceList->forceFill(['meta_attributes' => json_encode($attributes)])->save());
    }
}
