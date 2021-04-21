<?php

namespace App\Queries;

use App\Enum\OpportunityStatus;
use App\Models\Opportunity;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class OpportunityQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function paginateLostOpportunitiesQuery(?Request $request = null): Builder
    {
        return $this->paginateOpportunitiesQuery($request)
            ->where('opportunities.status', OpportunityStatus::LOST);
    }

    public function paginateOkOpportunitiesQuery(?Request $request = null): Builder
    {
        return $this->paginateOpportunitiesQuery($request)
            ->where('opportunities.status', OpportunityStatus::NOT_LOST);
    }

    public function paginateOpportunitiesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Opportunity();

        $query = $model->newQuery()
            ->select(
                'opportunities.id',
                'opportunities.user_id',
                'companies.id as company_id',
                'companies.name as account_name',
                'contract_types.type_short_name as opportunity_type',
                'opportunities.project_name',
                'users.user_fullname as account_manager_name',
                'opportunities.opportunity_closing_date',
                'opportunities.base_opportunity_amount as opportunity_amount',
                'opportunities.opportunity_start_date',
                'opportunities.opportunity_end_date',
                'opportunities.sale_action_name',
                'opportunities.status',
                'opportunities.status_reason',
                'opportunities.created_at'
            )
            ->doesntHave('worldwideQuotes')
            ->leftJoin('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'opportunities.contract_type_id');
            })
            ->leftJoin('users', function (JoinClause $join) {
                $join->on('users.id', 'opportunities.account_manager_id');
            })
            ->leftJoin('companies', function (JoinClause $join) {
                $join->on('companies.id', 'opportunities.primary_account_id');
            });

        if (filled($searchQuery = $request->query('search'))) {
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
                \App\Http\Query\OrderByAccountName::class,
                \App\Http\Query\OrderByProjectName::class,
                \App\Http\Query\OrderByOpportunityType::class,
                \App\Http\Query\OrderByOpportunityAmount::class,
                \App\Http\Query\OrderByOpportunityStartDate::class,
                \App\Http\Query\OrderByOpportunityEndDate::class,
                \App\Http\Query\OrderByOpportunityClosingDate::class,
                \App\Http\Query\OrderBySaleActionName::class,
                \App\Http\Query\OrderByAccountManagerName::class,
                \App\Http\Query\OrderByStatus::class,
                \App\Http\Query\OrderByStatusReason::class,
                \App\Http\Query\OrderByCreatedAt::class,
                (new \App\Http\Query\OrderByUpdatedAt)->qualifyColumnName(),
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }
}
