<?php

namespace App\Queries;

use App\Enum\OpportunityStatus;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Pipeline\PipelineStage;
use App\Models\Quote\WorldwideQuote;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use App\Services\Pipeline\PipelineEntityService;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class OpportunityQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function paginateLostOpportunitiesQuery(?Request $request = null): Builder
    {
        return $this->paginateQuotedOpportunitiesQuery($request)
            ->where('opportunities.status', OpportunityStatus::LOST);
    }

    public function paginateOkOpportunitiesQuery(?Request $request = null): Builder
    {
        return $this->paginateQuotedOpportunitiesQuery($request)
            ->where('opportunities.status', OpportunityStatus::NOT_LOST);
    }

    public function listOfCompanyOpportunitiesQuery(Company $company, ?Request $request = null): Builder
    {
        return tap($this->paginateOpportunitiesQuery($request), function (Builder $builder) use ($company) {
            $builder
                ->withExists('worldwideQuotes')
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
            ])
            ->leftJoin('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'opportunities.contract_type_id');
            })
            ->leftJoin('users', function (JoinClause $join) {
                $join->on('users.id', 'opportunities.account_manager_id');
            })
            ->leftJoin('companies', function (JoinClause $join) {
                $join->on('companies.id', 'opportunities.primary_account_id');
            });

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'account_name',
                'project_name',
                'opportunity_type',
                'opportunity_amount',
                'opportunity_start_date',
                'opportunity_end_date',
                'opportunity_closing_date',
                'sale_action_name',
                'account_manager_name',
                'status',
                'status_reason',
                'created_at',
                'updated_at',
            ])
            ->qualifyOrderFields(
                account_name: 'companies.name',
                project_name: $model->qualifyColumn('project_name'),
                opportunity_type: 'contract_types.type_short_name',
                opportunity_amount: $model->qualifyColumn('opportunities.base_opportunity_amount'),
                opportunity_start_date: $model->qualifyColumn('opportunity_start_date'),
                opportunity_end_date: $model->qualifyColumn('opportunity_end_date'),
                opportunity_closing_date: $model->qualifyColumn('opportunity_closing_date'),
                sale_action_name: $model->qualifyColumn('sale_action_name'),
                account_manager_name: 'users.user_fullname',
                status: $model->qualifyColumn('status'),
                status_reason: $model->qualifyColumn('status_reason'),
                created_at: $model->getQualifiedCreatedAtColumn(),
                updated_at: $model->getQualifiedUpdatedAtColumn(),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function paginateQuotedOpportunitiesQuery(?Request $request = null): Builder
    {
        return $this->paginateOpportunitiesQuery($request)
            ->addSelect([
                new Expression('0 as quotes_exist'),
            ])
            ->doesntHave('worldwideQuotes');
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
