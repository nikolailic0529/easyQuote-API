<?php

namespace App\Jobs;

use App\Repositories\Concerns\ManagesSchemalessAttributes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Database\{
    Query\Builder as QueryBuilder, Query\JoinClause
};
use Illuminate\Support\Facades\DB;
use App\Models\{
    Quote\BaseQuote, QuoteFile\QuoteFile
};

class RetrievePriceAttributes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ManagesSchemalessAttributes;

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
            ->join(
                'template_fields',
                fn (JoinClause $join) =>
                $join->on('template_fields.id', '=', 'quote_field_column.template_field_id')
                    ->whereIn('template_fields.name', BaseQuote::PRICE_ATTRIBUTES_MAPPING)
            )
            ->toBase()
            ->pluck('importable_column_id', 'template_fields.name');

        $subQuery = $priceList->rowsData()->toBase()
            ->whereNotNull('columns_data')
            ->when(true, function (QueryBuilder $query) use ($mapping) {
                $mapping->each(fn ($id, $column) => $this->unpivotJsonColumn($query, 'columns_data', 'importable_column_id', $id, 'value', $column));
            });

        $importedColumns = DB::query()->fromSub($subQuery, 'columns')->groupBy(...$mapping->keys())->get();

        $attributes = [];

        $mapping->keys()->each(function ($column) use (&$attributes, $importedColumns) {
            data_set($attributes, $column, $importedColumns->pluck($column)->toArray());
        });

        $attributes = array_merge_recursive($priceList->meta_attributes, $attributes);
        $attributes = array_map(fn ($attribute) => array_values(array_flip(array_flip($attribute))), $attributes);

        $priceList->storeMetaAttributes($attributes);

        $this->quote->fill($priceList->formatted_meta_attributes)->saveWithoutEvents();
    }
}
