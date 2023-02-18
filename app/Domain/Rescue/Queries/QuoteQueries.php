<?php

namespace App\Domain\Rescue\Queries;

use App\Domain\Rescue\Concerns\ManagesSchemalessAttributes;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Template\Models\TemplateField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class QuoteQueries
{
    use ManagesSchemalessAttributes;

    public function columnsMappingQuery(BaseQuote $quote)
    {
        $mappingQuery = $quote->fieldsColumns()->getQuery();

        return TemplateField::select(
            'template_fields.id as template_field_id',
            'template_fields.name as template_field_name',
            $mappingQuery->qualifyColumn('importable_column_id'),
            $mappingQuery->qualifyColumn('is_default_enabled'),
            $mappingQuery->qualifyColumn('is_preview_visible'),
            $mappingQuery->qualifyColumn('sort'),
        )
        ->leftJoin($mappingQuery->getModel()->getTable(), fn (JoinClause $join) => $join->where($quote->fieldsColumns()->getQualifiedForeignKeyName(), $quote->getKey())->on('template_fields.id', '=', $mappingQuery->qualifyColumn('template_field_id')))
        ->where('template_fields.is_system', true)
        ->whereIn('template_fields.name', config('quote-mapping.rescue_quote.fields'))
        ->orderBy('template_fields.order');
    }

    public function searchRowsQuery(BaseQuote $quote, string $search = '', ?string $groupId = null): BaseBuilder
    {
        $inputs = collect(explode(',', $search))->map('trim')->filter()->values();

        $rowsIds = transform($groupId, fn (): array => $quote->group_description->firstWhere('id', $groupId)->rows_ids ?? []);

        return $this->mappedOrderedRowsQuery($quote)
            ->where(function (BaseBuilder $query) use ($rowsIds, $inputs) {
                $query->whereRaw("columns_data->'$.*.value' like ?", ['%'.$inputs->shift().'%'])
                    ->tap(function (BaseBuilder $query) use ($inputs) {
                        $inputs->each(fn ($input) => $query->orWhereRaw("columns_data->'$.*.value' like ?", ['%'.$input.'%']));
                    })
                    ->when($rowsIds, fn (BaseBuilder $query) => $query->orWhereIn('id', $rowsIds));
            })
            ->addSelect(DB::raw('TRUE AS `is_selected`'));
    }

    public function mappedSelectedRowsQuery($quote, bool $ordered = false): BaseBuilder
    {
        $query = $ordered ? $this->mappedOrderedRowsQuery($quote) : DB::query()->fromSub($this->mappedRowsQuery($quote), 'mapped_rows');

        return $query
            ->when(
                $quote->groupsReady(),
                fn (BaseBuilder $query) => $query->whereIn('id', $quote->groupedRows()),
                fn (BaseBuilder $query) => $query->where('is_selected', true)
            );
    }

    public function mappedOrderedRowsQuery(BaseQuote $quote): BaseBuilder
    {
        $subQuery = $this->mappedRowsQuery($quote);
        $query = DB::query()->fromSub($subQuery, 'mapped_rows');

        $mapping = $quote->fieldsColumns;
        $columns = $mapping->pluck('templateField.name');

        $query->addSelect('id', 'replicated_row_id', 'is_selected', ...$columns);

        /* Sorting by columns. */
        $mapping->each(
            fn ($map) =>
            /* Except the price column to be able calculate price. */
            $query->when($map->templateField->name !== 'price', fn (BaseBuilder $query) => $query->addSelect($map->templateField->name))
                ->when(filled($map->sort), fn (BaseBuilder $query) => $query->orderBy($map->templateField->name, $map->sort))
        );

        return $query;
    }

    public function mappedRowsQuery(BaseQuote $quote): BaseBuilder
    {
        $query = DB::table('imported_rows')
            ->join('customers', fn (JoinClause $join) => $join->where('customers.id', $quote->customer_id))
            ->whereNull('imported_rows.deleted_at')
            ->whereNotNull('imported_rows.columns_data')
            ->where('imported_rows.quote_file_id', $quote->distributor_file_id)
            ->where('imported_rows.page', '>=', function (BaseBuilder $query) use ($quote) {
                $query->select('imported_page')->from('quote_files')->where('quote_files.id', $quote->distributor_file_id)->limit(1);
            });

        $mapping = $quote->fieldsColumns;

        $exchangeRate = $quote->convertExchangeRate(1);

        $customerAttributesMap = ['date_from' => 'customers.support_start', 'date_to' => 'customers.support_end'];

        $query->select('imported_rows.id', 'imported_rows.replicated_row_id', 'imported_rows.is_selected', 'imported_rows.is_one_pay', 'imported_rows.columns_data', 'customers.support_start as customer_support_start', 'customers.support_end as customer_support_end');

        $mapping->each(function ($map) use ($query, $customerAttributesMap) {
            if (!$map->is_default_enabled) {
                if ($map->importable_column_id !== null) {
                    $this->unpivotJsonColumn($query, 'columns_data', "$.\"{$map->importable_column_id}\".value", $map->templateField->name);
                } else {
                    $query->selectRaw("NULL as `{$map->templateField->name}`");
                }

                return true;
            }

            switch ($map->templateField->name) {
                case 'date_from':
                case 'date_to':
                    $defaultCustomerDate = optional($customerAttributesMap)[$map->templateField->name];
                    $query->selectRaw("DATE_FORMAT({$defaultCustomerDate}, '%d/%m/%Y') as {$map->templateField->name}");
                    break;
                case 'qty':
                    $query->selectRaw("1 as {$map->templateField->name}");
                    break;
            }
        });

        $query = DB::query()->fromSub($query, 'imported_rows')->addSelect('id', 'replicated_row_id', 'is_selected', 'is_one_pay', 'columns_data');

        $defaults = collect(['price' => 0, 'date_from' => '`customer_support_start`', 'date_to' => '`customer_support_end`']);

        $mapping->each(function ($map) use ($query, $defaults, $exchangeRate) {
            if ($map->is_default_enabled) {
                $query->addSelect($map->templateField->name);

                return true;
            }

            switch ($map->templateField->name) {
                case 'price':
                    $query->selectRaw('CAST(ExtractDecimal(`price`) * ? AS DECIMAL(15,2)) as `price`', [$exchangeRate]);
                    break;
                case 'date_from':
                case 'date_to':
                    $this->parseColumnDate($query, $map->templateField->name, $defaults->get($map->templateField->name));
                    break;
                case 'qty':
                    $query->selectRaw('GREATEST(CAST(`qty` AS UNSIGNED), 1) AS `qty`');
                    break;
                default:
                    $query->addSelect($map->templateField->name);
                    break;
            }
        });

        /* Select default values when the related mapping doesn't exist. */
        $defaults->each(
            fn ($value, $column) => $query->unless($mapping->contains('templateField.name', $column), fn (BaseBuilder $query) => $query->selectRaw("{$value} AS `{$column}`"))
        );

        $query = DB::query()->fromSub($query, 'rows');

        $columns = $mapping->pluck('templateField.name')->flip()->except('price')->flip();
        $query->addSelect('id', 'replicated_row_id', 'is_selected', 'columns_data', ...$columns);

        /* Calculating price based on date_from & date_to when related option is selected. */
        $query->when(
            $quote->calculate_list_price,
            fn (BaseBuilder $query) => $query->selectRaw("(CAST(IF(`is_one_pay`, `price`, `price` / 30 * GREATEST(DATEDIFF(STR_TO_DATE(`date_to`, '%d/%m/%Y'), STR_TO_DATE(`date_from`, '%d/%m/%Y')), 0)) AS DECIMAL(15,2))) as `price`"),
            fn (BaseBuilder $query) => $query->addSelect('price')
        );

        return $query;
    }

    public function quoteByRfqNumberQuery(string $rfqNumber): Builder
    {
        return Quote::query()
            ->whereNotNull('submitted_at')
            ->whereNotNull('activated_at')
            ->whereHas('customer', function (Builder $builder) use ($rfqNumber) {
                $builder->where('rfq', $rfqNumber);
            });
    }
}
