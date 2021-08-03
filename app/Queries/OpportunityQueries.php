<?php

namespace App\Queries;

use App\Enum\OpportunityStatus;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Pipeline\PipelineStage;
use App\Models\Quote\WorldwideQuote;
use App\Services\ElasticsearchQuery;
use App\Services\Pipeline\PipelineEntityService;
use DB;
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

    public function listOkOpportunitiesOfCompanyQuery(Company $company, ?Request $request = null): Builder
    {
        return tap($this->paginateOkOpportunitiesQuery($request), function (Builder $builder) use ($company) {

            $builder
                ->where($builder->qualifyColumn('primary_account_id'), $company->getKey());

        });
    }

    public function paginateOpportunitiesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Opportunity();
        $quoteModel = new WorldwideQuote();

        $query = $model->newQuery()
            ->select([
                'opportunities.id',
                'opportunities.user_id',
                'opportunities.account_manager_id',
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
                'opportunities.created_at',
                DB::raw('0 as quotes_exist')
            ])
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
                    ElasticsearchQuery::new()
                        ->modelIndex($model)
                        ->queryString($searchQuery)
                        ->escapeQueryString()
                        ->wrapQueryString()
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

    public function opportunitiesOfPipelineStageQuery(PipelineStage $pipelineStage): Builder
    {
        $opportunityModel = new Opportunity();

        return $opportunityModel->newQuery()
            ->where('pipeline_id', $pipelineStage->pipeline()->getParentKey())
            ->where('sale_action_name', PipelineEntityService::qualifyPipelineStageName($pipelineStage))
            ->select([
                $opportunityModel->getQualifiedKeyName(),
                $opportunityModel->qualifyColumn('user_id'),
                $opportunityModel->qualifyColumn('account_manager_id'),
                'companies.id as primary_account.id',
                'companies.name as primary_account.name',
                'companies.phone as primary_account.phone',
                'companies.email as primary_account.email',
                $opportunityModel->qualifyColumn('project_name'),
                'users.user_fullname as account_manager_name',
                $opportunityModel->qualifyColumn('opportunity_closing_date'),
                $opportunityModel->qualifyColumn('base_opportunity_amount'),
                $opportunityModel->qualifyColumn('opportunity_amount'),
                $opportunityModel->qualifyColumn('opportunity_amount_currency_code'),
//                "{$opportunityModel->qualifyColumn('base_opportunity_amount')} as opportunity_amount",
                'primary_account_contact.first_name as primary_account_contact.first_name',
                'primary_account_contact.last_name as primary_account_contact.last_name',
                'primary_account_contact.phone as primary_account_contact.phone',
                'primary_account_contact.email as primary_account_contact.email',
                $opportunityModel->qualifyColumn('opportunity_start_date'),
                $opportunityModel->qualifyColumn('opportunity_end_date'),
                $opportunityModel->qualifyColumn('ranking'),
                $opportunityModel->qualifyColumn('status'),
                $opportunityModel->qualifyColumn('status_reason'),
                $opportunityModel->qualifyColumn('created_at'),
            ])
            ->leftJoin('users', function (JoinClause $join) {
                $join->on('users.id', 'opportunities.account_manager_id');
            })
            ->leftJoin('companies', function (JoinClause $join) {
                $join->on('companies.id', 'opportunities.primary_account_id');
            })
            ->leftJoin('contacts as primary_account_contact', function (JoinClause $join) {
                $join->on('primary_account_contact.id', 'opportunities.primary_account_contact_id');
            });
    }
}
