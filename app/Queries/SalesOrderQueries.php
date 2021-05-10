<?php

namespace App\Queries;

use App\Models\SalesOrder;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class SalesOrderQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function paginateDraftedOrdersQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $query = SalesOrder::query()
            ->select(
                'sales_orders.id',
                'sales_orders.user_id',
                'sales_orders.worldwide_quote_id',
                'worldwide_quotes.contract_type_id',
                'worldwide_quotes.opportunity_id',
                'sales_orders.order_number',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.sequence_number',
                'companies.name as customer_name',
                'contract_types.type_short_name as order_type',
                'sales_orders.status',
                'sales_orders.created_at',
                'sales_orders.activated_at'
            )
            ->join('worldwide_quotes', function (JoinClause $join) {
                $join->on('worldwide_quotes.id', 'sales_orders.worldwide_quote_id');
            })
            ->join('worldwide_quote_versions as active_quote_version', function (JoinClause $join) {
                $join->on('active_quote_version.id', 'worldwide_quotes.active_version_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->whereNull('sales_orders.submitted_at');

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex(new SalesOrder())
                        ->queryString('*'.trim($searchQuery, '*').'*')
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                new \App\Http\Query\ActiveFirst('sales_orders.is_active'),
                (new \App\Http\Query\OrderByCreatedAt)->qualifyColumnName(),
                (new \App\Http\Query\OrderByUpdatedAt)->qualifyColumnName(),
                \App\Http\Query\OrderByOrderType::class,
                \App\Http\Query\OrderByRfqNumber::class,
                \App\Http\Query\OrderByOrderNumber::class,
                \App\Http\Query\OrderByStatus::class,
                \App\Http\Query\OrderByCustomerName::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }

    public function paginateSubmittedOrdersQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $query = SalesOrder::query()
            ->select(
                'sales_orders.id',
                'sales_orders.user_id',
                'sales_orders.worldwide_quote_id',
                'worldwide_quotes.contract_type_id',
                'worldwide_quotes.opportunity_id',
                'sales_orders.order_number',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.sequence_number',
                'companies.name as customer_name',
                'contract_types.type_short_name as order_type',
                'sales_orders.status',
                'sales_orders.failure_reason',
                'sales_orders.status_reason',
                'sales_orders.created_at',
                'sales_orders.activated_at'
            )
            ->join('worldwide_quotes', function (JoinClause $join) {
                $join->on('worldwide_quotes.id', 'sales_orders.worldwide_quote_id');
            })
            ->join('worldwide_quote_versions as active_quote_version', function (JoinClause $join) {
                $join->on('active_quote_version.id', 'worldwide_quotes.active_version_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->whereNotNull('sales_orders.submitted_at');

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex(new SalesOrder())
                        ->queryString('*'.trim($searchQuery, '*').'*')
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                new \App\Http\Query\ActiveFirst('sales_orders.is_active'),
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByOrderType::class,
                \App\Http\Query\OrderByRfqNumber::class,
                \App\Http\Query\OrderByOrderNumber::class,
                \App\Http\Query\OrderByStatus::class,
                \App\Http\Query\OrderByCustomerName::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }
}
