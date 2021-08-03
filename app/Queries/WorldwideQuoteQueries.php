<?php

namespace App\Queries;

use App\DTO\WorldwideQuote\AssetsLookupData;
use App\Enum\QuoteStatus;
use App\Models\Company;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup;
use App\Services\ElasticsearchQuery;
use DB;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;

class WorldwideQuoteQueries
{
    protected ConnectionInterface $connection;

    protected Elasticsearch $elasticsearch;

    protected Pipeline $pipeline;

    public function __construct(ConnectionInterface $connection,
                                Elasticsearch $elasticsearch,
                                Pipeline $pipeline)
    {
        $this->elasticsearch = $elasticsearch;
        $this->pipeline = $pipeline;
        $this->connection = $connection;
    }

    public function aliveDraftedListingQuery(Request $request = null): Builder
    {
        return $this->draftedListingQuery($request)
            ->with(['versions' => function (Relation $relation) {
                $relation->select([
                    'worldwide_quote_versions.id',
                    'worldwide_quote_versions.worldwide_quote_id',
                    'worldwide_quote_versions.user_id',
                    'users.user_fullname',
                    'worldwide_quote_versions.user_version_sequence_number',
                    'worldwide_quote_versions.updated_at'
                ])
                    ->join('users', function (JoinClause $join) {
                        $join->on('users.id', 'worldwide_quote_versions.user_id');
                    })
                    ->orderByDesc('updated_at');
            }])
            ->where('worldwide_quotes.status', QuoteStatus::ALIVE);
    }

    public function draftedListingQuery(Request $request = null): Builder
    {
        return $this->listingQuery($request)
            ->whereNull('worldwide_quotes.submitted_at');
    }

    public function listingQuery(Request $request = null): Builder
    {
        $request ??= new Request;

        $query = WorldwideQuote::query()
            ->join('worldwide_quote_versions as active_version', function (JoinClause $joinClause) {
                $joinClause->on('active_version.id', 'worldwide_quotes.active_version_id');
            })
            ->select(
                'worldwide_quotes.id',
                'worldwide_quotes.active_version_id',
                'worldwide_quotes.user_id',
                'worldwide_quotes.opportunity_id',
                'worldwide_quotes.contract_type_id',

                'sales_orders.id as sales_order_id',
                'sales_orders.submitted_at as sales_order_submitted_at',

                'contract_types.type_short_name as type_name',
                'worldwide_quotes.quote_number as rfq_number',
                'active_version.company_id',
                'active_version.completeness',

                'worldwide_quotes.status',
                'worldwide_quotes.status_reason',

                'worldwide_quotes.created_at',
                'worldwide_quotes.updated_at',
                'worldwide_quotes.activated_at',
                DB::raw('(sales_orders.id is not null) as sales_order_exists')
            )
            ->join('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->join('opportunities', function (JoinClause $join) {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'opportunities.primary_account_id');
            })
            ->leftJoin('sales_orders', function (JoinClause $join) {
                $join->on('sales_orders.worldwide_quote_id', 'worldwide_quotes.id')
                    ->whereNull('sales_orders.deleted_at');
            })
            ->addSelect([
                'user_fullname' => User::query()->select('user_fullname')->whereColumn('users.id', 'worldwide_quotes.user_id')->limit(1),
                'company_name' => Company::query()->select('name')->whereColumn('companies.id', 'active_version.company_id')->limit(1),
                'companies.name as customer_name',
                'opportunities.opportunity_closing_date as valid_until_date',
                'opportunities.opportunity_start_date as customer_support_start_date',
                'opportunities.opportunity_end_date as customer_support_end_date',
            ]);

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    ElasticsearchQuery::new()
                        ->modelIndex(new WorldwideQuote)
                        ->queryString($searchQuery)
                        ->escapeQueryString()
                        ->wrapQueryString()
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        $this->pipeline->send($query)
            ->through([
                new \App\Http\Query\ActiveFirst('worldwide_quotes.is_active'),
                \App\Http\Query\OrderByTypeName::class,
                \App\Http\Query\OrderByCustomerName::class,
                new \App\Http\Query\Quote\OrderByCompleteness('active_version.completeness'),
                \App\Http\Query\OrderByUserFullname::class,
                \App\Http\Query\OrderByRfqNumber::class,
                \App\Http\Query\OrderByValidUntilDate::class,
                \App\Http\Query\OrderByCustomerSupportStartDate::class,
                \App\Http\Query\OrderByCustomerSupportEndDate::class,
                \App\Http\Query\OrderByStatus::class,
                \App\Http\Query\OrderByStatusReason::class,
                \App\Http\Query\OrderByCreatedAt::class,
                (new \App\Http\Query\OrderByUpdatedAt)->qualifyColumnName(),
                new \App\Http\Query\DefaultOrderBy('worldwide_quotes.updated_at'),
            ])
            ->thenReturn();

        return $query;
    }

    public function deadDraftedListingQuery(Request $request = null): Builder
    {
        return $this->draftedListingQuery($request)
            ->where('worldwide_quotes.status', QuoteStatus::DEAD);
    }

    public function aliveSubmittedListingQuery(Request $request = null): Builder
    {
        return $this->submittedListingQuery($request)
            ->where('worldwide_quotes.status', QuoteStatus::ALIVE);
    }

    public function submittedListingQuery(Request $request = null): Builder
    {
        $distributorFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.active_version_id')
            ->has('opportunitySupplier')
            ->has('distributorFile')
            ->limit(1);

        $scheduleFileExistenceQuery = WorldwideDistribution::query()->selectRaw('1')
            ->whereColumn('worldwide_distributions.worldwide_quote_id', 'worldwide_quotes.active_version_id')
            ->has('opportunitySupplier')
            ->has('scheduleFile')
            ->limit(1);

        return $this->listingQuery($request)
            ->addSelect([

                'has_distributor_files' => $this->connection->query()
                    ->selectRaw('exists ('.$distributorFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),
                'has_schedule_files' => $this->connection->query()
                    ->selectRaw('exists ('.$scheduleFileExistenceQuery->toSql().')', $scheduleFileExistenceQuery->getBindings()),
            ])
            ->whereNotNull('worldwide_quotes.submitted_at');
    }

    public function deadSubmittedListingQuery(Request $request = null): Builder
    {
        return $this->submittedListingQuery($request)
            ->where('worldwide_quotes.status', QuoteStatus::DEAD);
    }

    public function assetsLookupQuery(WorldwideQuoteVersion $quoteVersion, AssetsLookupData $data): Builder
    {
        /** @var WorldwideQuoteAsset $assetModel */
        $assetModel = $quoteVersion->assets()->getRelated();
        $vendorRelationship = $assetModel->vendor();

        $selectColumns = [
            $assetModel->getQualifiedKeyName(),
            $assetModel->qualifyColumn('vendor_id'),
            $assetModel->qualifyColumn('machine_address_id'),
            $assetModel->qualifyColumn('buy_currency_id'),
            \Illuminate\Support\Facades\DB::raw('1 as is_selected'),
            $assetModel->qualifyColumn('country'),
            $assetModel->qualifyColumn('serial_no'),
            $assetModel->qualifyColumn('sku'),
            $assetModel->qualifyColumn('service_sku'),
            $assetModel->qualifyColumn('product_name'),
            $assetModel->qualifyColumn('expiry_date'),
            $assetModel->qualifyColumn('service_level_description'),
            $assetModel->qualifyColumn('service_level_data'),
            $assetModel->qualifyColumn('price'),
            $assetModel->qualifyColumn('original_price'),
            $assetModel->qualifyColumn('exchange_rate_value'),
            $assetModel->qualifyColumn('exchange_rate_margin'),
            "{$vendorRelationship->qualifyColumn('short_code')} as vendor_short_code",
        ];

        return $quoteVersion->assets()->getQuery()
            ->join($vendorRelationship->getRelated()->getTable(), function (JoinClause $join) use ($assetModel, $vendorRelationship) {
                $join->on($vendorRelationship->getQualifiedForeignKeyName(), $vendorRelationship->getQualifiedOwnerKeyName());
            })
            ->where(function (Builder $builder) use ($data, $assetModel, $vendorRelationship) {
                $input = array_values($data->input);

                $columns = [
                    $assetModel->qualifyColumn('sku'),
                    $assetModel->qualifyColumn('service_sku'),
                    $assetModel->qualifyColumn('product_name'),
                    $assetModel->qualifyColumn('serial_no'),
                    $assetModel->qualifyColumn('price'),
                    $assetModel->qualifyColumn('service_level_description'),
                    $vendorRelationship->qualifyColumn('short_code'),
                    $vendorRelationship->qualifyColumn('name'),
                ];

                foreach ($input as $string) {
                    foreach ($columns as $column) {
                        $builder->orWhere($column, 'like', "%$string%");
                    }
                }
            })
            ->select($selectColumns)
            ->when($data->assets_group instanceof WorldwideQuoteAssetsGroup, function (Builder $builder) use ($assetModel, $vendorRelationship, $data, $selectColumns) {
                $builder->union(
                    $data->assets_group->assets()->getQuery()
                        ->join($vendorRelationship->getRelated()->getTable(), function (JoinClause $join) use ($assetModel, $vendorRelationship) {
                            $join->on($vendorRelationship->getQualifiedForeignKeyName(), $vendorRelationship->getQualifiedOwnerKeyName());
                        })
                        ->select($selectColumns)
                );
            })
            ->with(['machineAddress', 'buyCurrency']);
    }
}
