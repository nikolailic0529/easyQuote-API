<?php

namespace App\Queries;

use App\Models\Company;
use App\Models\SalesOrder;
use App\Models\User;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class SalesOrderQueries
{
    public function __construct(protected Elasticsearch $elasticsearch,
                                protected Gate $gate)
    {
    }

    public function listOfCompanySalesOrdersQuery(Company $company, ?Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var User|null $user */
        $user = $request->user();

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
                'primary_account.name as customer_name',
                'companies.name as company_name',
                'contract_types.type_short_name as order_type',
                'sales_orders.status',
                'sales_orders.failure_reason',
                'sales_orders.status_reason',
                'sales_orders.created_at',
                'sales_orders.updated_at',
                'sales_orders.submitted_at',
                'sales_orders.activated_at'
            )
            ->join('worldwide_quotes', function (JoinClause $join) {
                $join->on('worldwide_quotes.id', 'sales_orders.worldwide_quote_id');
            })
            ->join('worldwide_quote_versions as active_quote_version', function (JoinClause $join) {
                $join->on('active_quote_version.id', 'worldwide_quotes.active_version_id');
            })
            ->join('opportunities', function (JoinClause $join) use ($company) {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id')
                    ->where('opportunities.primary_account_id', $company->getKey());
            })
            ->join('companies as primary_account', function (JoinClause $join) {
                $join->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->when($this->gate->denies('viewAnyOwnerEntities', SalesOrder::class), function (Builder $builder) use ($user) {

                $builder->where($builder->qualifyColumn('user_id'), $user?->getKey())
                    ->orWhereIn($builder->qualifyColumn('user_id'), User::query()->select('id')->join('team_team_leader', function (JoinClause $join) use ($user) {
                        $join->on('users.team_id', 'team_team_leader.team_id')
                            ->where('team_team_leader.id', $user?->getKey());
                    }));

            });

        return tap($query, function (Builder $builder) {
            $builder->orderBy('updated_at', 'desc');
        });
    }

    public function paginateDraftedOrdersQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new SalesOrder();

        $query = $model->newQuery()
            ->select(
                'sales_orders.id',
                'sales_orders.user_id',
                'sales_orders.worldwide_quote_id',
                'worldwide_quotes.contract_type_id',
                'worldwide_quotes.opportunity_id',
                'sales_orders.order_number',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.sequence_number',
                'primary_account.name as customer_name',
                'companies.name as company_name',
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
            ->join('opportunities', function (JoinClause $join) {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies as primary_account', function (JoinClause $join) {
                $join->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->whereNull('sales_orders.submitted_at')
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
                'updated_at',
                'order_type',
                'rfq_number',
                'status',
                'customer_name',
                'company_name',
            ])
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
                updated_at: $model->getQualifiedUpdatedAtColumn(),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function paginateSubmittedOrdersQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new SalesOrder();

        $query = $model->newQuery()
            ->select(
                'sales_orders.id',
                'sales_orders.user_id',
                'sales_orders.worldwide_quote_id',
                'worldwide_quotes.contract_type_id',
                'worldwide_quotes.opportunity_id',
                'sales_orders.order_number',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.sequence_number',
                'primary_account.name as customer_name',
                'companies.name as company_name',
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
            ->join('opportunities', function (JoinClause $join) {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies as primary_account', function (JoinClause $join) {
                $join->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->join('companies', function (JoinClause $join) {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->whereNotNull('sales_orders.submitted_at')
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
                'updated_at',
                'order_type',
                'rfq_number',
                'status',
                'customer_name',
                'company_name',
            ])
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
                updated_at: $model->getQualifiedUpdatedAtColumn(),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
