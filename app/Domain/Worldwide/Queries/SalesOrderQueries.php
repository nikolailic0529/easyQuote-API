<?php

namespace App\Domain\Worldwide\Queries;

use App\Domain\Company\Models\Company;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Queries\Scopes\SalesOrderScope;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class SalesOrderQueries
{
    public function __construct(
        protected readonly Elasticsearch $elasticsearch,
        protected readonly Gate $gate
    ) {
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
                'end_user.id as end_user_id',
                'end_user.name as end_user_name',
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
            ->join('worldwide_quotes', static function (JoinClause $join): void {
                $join->on('worldwide_quotes.id', 'sales_orders.worldwide_quote_id');
            })
            ->join('worldwide_quote_versions as active_quote_version', static function (JoinClause $join): void {
                $join->on('active_quote_version.id', 'worldwide_quotes.active_version_id');
            })
            ->join('opportunities', static function (JoinClause $join) use ($company): void {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id')
                    ->where(static function (JoinClause $join) use ($company): void {
                        $join->where('opportunities.primary_account_id', $company->getKey())
                            ->orWhere('opportunities.end_user_id', $company->getKey());
                    });
            })
            ->join('companies as primary_account', static function (JoinClause $join): void {
                $join->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->leftJoin('companies as end_user', static function (JoinClause $join): void {
                $join->on('end_user.id', 'opportunities.end_user_id');
            })
            ->join('companies', static function (JoinClause $join): void {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', static function (JoinClause $join): void {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->tap(SalesOrderScope::from($request, $this->gate));

        return tap($query, static function (Builder $builder): void {
            $builder->orderBy('updated_at', 'desc');
        });
    }

    public function paginateDraftedOrdersQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new SalesOrder();

        $query = $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->user()->getQualifiedForeignKeyName(),
                $model->worldwideQuote()->getQualifiedForeignKeyName(),
                ...$model->qualifyColumns([
                    'order_number',
                    'assets_count',
                    'status',
                    'created_at',
                    'activated_at',
                ]),
                'worldwide_quotes.contract_type_id',
                'worldwide_quotes.opportunity_id',
                'opportunities.project_name as opportunity_name',
                'opportunities.sales_unit_id as sales_unit_id',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.sequence_number',
                'primary_account.name as customer_name',
                'end_user.name as end_user_name',
                'account_manager.user_fullname as account_manager_name',
                'account_manager.email as account_manager_email',
                'companies.name as company_name',
                'contract_types.type_short_name as order_type',
            ])
            ->join('worldwide_quotes', static function (JoinClause $join): void {
                $join->on('worldwide_quotes.id', 'sales_orders.worldwide_quote_id');
            })
            ->join('worldwide_quote_versions as active_quote_version', static function (JoinClause $join): void {
                $join->on('active_quote_version.id', 'worldwide_quotes.active_version_id');
            })
            ->join('opportunities', static function (JoinClause $join): void {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies as primary_account', static function (JoinClause $join): void {
                $join->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->leftJoin('companies as end_user', static function (JoinClause $join): void {
                $join->on('end_user.id', 'opportunities.end_user_id');
            })
            ->leftJoin('users as account_manager', static function (JoinClause $join): void {
                $join->on('account_manager.id', 'opportunities.account_manager_id');
            })
            ->join('companies', static function (JoinClause $join): void {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', static function (JoinClause $join): void {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->tap(SalesOrderScope::from($request, $this->gate))
            ->whereNull('sales_orders.submitted_at')
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(
                'created_at',
                'updated_at',
                'order_type',
                'rfq_number',
                'status',
                'customer_name',
                'company_name',
                'opportunity_name',
                'assets_count',
                'end_user_name',
                'account_manager_name',
                'account_manager_email',
            )
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
            ->select([
                $model->getQualifiedKeyName(),
                $model->user()->getQualifiedForeignKeyName(),
                $model->worldwideQuote()->getQualifiedForeignKeyName(),
                ...$model->qualifyColumns([
                    'order_number',
                    'assets_count',
                    'status',
                    'failure_reason',
                    'status_reason',
                    'created_at',
                    'activated_at',
                ]),
                'worldwide_quotes.contract_type_id',
                'worldwide_quotes.opportunity_id',
                'worldwide_quotes.quote_number as rfq_number',
                'worldwide_quotes.sequence_number',
                'opportunities.project_name as opportunity_name',
                'opportunities.sales_unit_id as sales_unit_id',
                'primary_account.name as customer_name',
                'primary_account.id as primary_account_id',
                'end_user.name as end_user_name',
                'end_user.id as end_user_id',
                'account_manager.user_fullname as account_manager_name',
                'account_manager.email as account_manager_email',
                'companies.name as company_name',
                'contract_types.type_short_name as order_type',
            ])
            ->join('worldwide_quotes', static function (JoinClause $join): void {
                $join->on('worldwide_quotes.id', 'sales_orders.worldwide_quote_id');
            })
            ->join('worldwide_quote_versions as active_quote_version', static function (JoinClause $join): void {
                $join->on('active_quote_version.id', 'worldwide_quotes.active_version_id');
            })
            ->join('opportunities', static function (JoinClause $join): void {
                $join->on('opportunities.id', 'worldwide_quotes.opportunity_id');
            })
            ->join('companies as primary_account', static function (JoinClause $join): void {
                $join->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->leftJoin('companies as end_user', static function (JoinClause $join): void {
                $join->on('end_user.id', 'opportunities.end_user_id');
            })
            ->leftJoin('users as account_manager', static function (JoinClause $join): void {
                $join->on('account_manager.id', 'opportunities.account_manager_id');
            })
            ->join('companies', static function (JoinClause $join): void {
                $join->on('companies.id', 'active_quote_version.company_id');
            })
            ->join('contract_types', static function (JoinClause $join): void {
                $join->on('contract_types.id', 'worldwide_quotes.contract_type_id');
            })
            ->tap(SalesOrderScope::from($request, $this->gate))
            ->whereNotNull('sales_orders.submitted_at')
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(
                'created_at',
                'updated_at',
                'order_type',
                'rfq_number',
                'status',
                'customer_name',
                'company_name',
                'assets_count',
                'end_user_name',
                'account_manager_name',
                'account_manager_email',
                'opportunity_name',
            )
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
                updated_at: $model->getQualifiedUpdatedAtColumn(),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
