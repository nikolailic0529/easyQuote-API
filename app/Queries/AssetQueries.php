<?php

namespace App\Queries;

use App\Models\Asset;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class AssetQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;

        $this->elasticsearch = $elasticsearch;
    }

    public function paginateAssetsQuery(Request $request = null): Builder
    {
        $request ??= new Request();
        $model = new Asset();

        $query = $model->newQuery()
            ->select([
                $model->qualifyColumn('id'),
                $model->qualifyColumn('asset_category_id'),
                'asset_categories.name as asset_category_name',
                $model->qualifyColumn('user_id'),
                $model->qualifyColumn('address_id'),
                $model->qualifyColumn('vendor_id'),
                $model->qualifyColumn('quote_id'),
                'customers.rfq as customer_rfq_number',
                $model->qualifyColumn('vendor_short_code'),

                $model->qualifyColumn('unit_price'),
                $model->qualifyColumn('base_warranty_start_date'),
                $model->qualifyColumn('base_warranty_end_date'),
                $model->qualifyColumn('active_warranty_start_date'),
                $model->qualifyColumn('active_warranty_end_date'),
                $model->qualifyColumn('product_number'),
                $model->qualifyColumn('serial_number'),
                $model->qualifyColumn('product_description'),
                $model->qualifyColumn('product_image'),
                $model->qualifyColumn('created_at'),
            ])
            ->join('asset_categories', 'asset_categories.id', $model->qualifyColumn('asset_category_id'))
            ->leftJoin('quotes', 'quotes.id', $model->qualifyColumn('quote_id'))
            ->leftJoin('customers', 'customers.id', 'quotes.customer_id');

        if (filled($searchQuery = $request->input('search')) && is_string($searchQuery)) {
            $hits = rescue(function () use ($model, $searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex($model)
                        ->queryString('*'.ElasticsearchQuery::escapeReservedChars($searchQuery).'*')
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByProductNumber::class,
                \App\Http\Query\OrderBySerialNumber::class,
                \App\Http\Query\OrderBySku::class,
                \App\Http\Query\OrderByBaseWarrantyStartDate::class,
                \App\Http\Query\OrderByBaseWarrantyEndDate::class,
                \App\Http\Query\OrderByActiveWarrantyStartDate::class,
                \App\Http\Query\OrderByActiveWarrantyEndDate::class,
                \App\Http\Query\OrderByVendorShortCode::class,
                \App\Http\Query\OrderByAssetCategory::class,
                \App\Http\Query\OrderByQuoteId::class,
                \App\Http\Query\FilterByLocation::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }

    public function locationsQuery(): Builder
    {
        return Asset::query()
            ->join('addresses', function (JoinClause $join) {
                $join->on('addresses.id', '=', 'assets.address_id')
                    ->whereNotNull('addresses.location_id')
                    ->whereNull('addresses.deleted_at');
            })
            ->join('locations', function (JoinClause $join) {
                $join->on('locations.id', '=', 'addresses.location_id')
                    ->whereNull('locations.deleted_at');
            })
            ->with('location')
            ->groupByRaw('locations.id');
    }

    public function aggregateByUserAndLocationQuery(string $locationId): BaseBuilder
    {
        return Asset::query()
            ->selectRaw('SUM(`unit_price`) AS `total_value`')
            ->selectRaw('COUNT(*) AS `total_count`')
            ->addSelect('user_id')
            ->groupBy('user_id')
            ->whereHas('location', fn(Builder $q) => $q->whereKey($locationId))
            ->toBase();
    }
}
