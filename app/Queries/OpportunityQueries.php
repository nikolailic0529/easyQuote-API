<?php

namespace App\Queries;

use App\Enum\OpportunityStatus;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Pipeline\PipelineStage;
use App\Models\Quote\WorldwideQuote;
use App\Queries\Pipeline\PerformElasticsearchSearch;
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
        $pipelineStageModel = new PipelineStage();
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
                "{$pipelineStageModel->qualifyColumn('stage_name')} as sale_action_name",
                'opportunities.status',
                'opportunities.status_reason',
                'opportunities.created_at',
            ])
            ->leftJoin('contract_types', function (JoinClause $join) {
                $join->on('contract_types.id', 'opportunities.contract_type_id');
            })
            ->leftJoin($pipelineStageModel->getTable(), $pipelineStageModel->getQualifiedKeyName(), $model->pipelineStage()->getQualifiedForeignKeyName())
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

    public function paginateOpportunitiesOfPipelineStageQuery(PipelineStage $pipelineStage, ?Request $request = null): Builder
    {
        $request ??= new Request();

        $opportunityModel = new Opportunity();

        $query = $this->opportunitiesOfPipelineStageQuery($pipelineStage)
            ->select([
                $opportunityModel->getQualifiedKeyName(),
                $opportunityModel->qualifyColumn('user_id'),
                $opportunityModel->qualifyColumn('account_manager_id'),
                $opportunityModel->qualifyColumn('primary_account_id'),
                $opportunityModel->qualifyColumn('primary_account_contact_id'),
                $opportunityModel->qualifyColumn('end_user_id'),
                $opportunityModel->qualifyColumn('project_name'),
                'users.user_fullname as account_manager_name',
                $opportunityModel->qualifyColumn('opportunity_closing_date'),
                $opportunityModel->qualifyColumn('base_opportunity_amount'),
                $opportunityModel->qualifyColumn('opportunity_amount'),
                $opportunityModel->qualifyColumn('opportunity_amount_currency_code'),

                'primary_account.name as primary_account_name',
                'primary_account.phone as primary_account_phone',
                'primary_account.email as primary_account_email',

                'primary_account_contact.first_name as primary_account_contact_first_name',
                'primary_account_contact.last_name as primary_account_contact_last_name',
                'primary_account_contact.phone as primary_account_contact_phone',
                'primary_account_contact.email as primary_account_contact_email',

                'end_user.name as end_user_name',
                'end_user.phone as end_user_phone',
                'end_user.email as end_user_email',

                $opportunityModel->qualifyColumn('opportunity_start_date'),
                $opportunityModel->qualifyColumn('opportunity_end_date'),
                $opportunityModel->qualifyColumn('ranking'),
                $opportunityModel->qualifyColumn('status'),
                $opportunityModel->qualifyColumn('status_reason'),
                $opportunityModel->qualifyColumn('is_contract_duration_checked'),
                $opportunityModel->qualifyColumn('contract_duration_months'),
                $opportunityModel->qualifyColumn('created_at'),

                new Expression("false as quotes_exist"),
            ])
            ->with([
                'accountManager:id,email,user_fullname',
                'primaryAccount:id,name,phone,email',
                'primaryAccount.image',
                'endUser:id,name,phone,email',
                'endUser.image',
                'primaryAccountContact:id,first_name,last_name,phone,email',
            ])
            ->leftJoin('users', function (JoinClause $join) {
                $join->on('users.id', 'opportunities.account_manager_id');
            })
            ->leftJoin('companies as primary_account', function (JoinClause $join) {
                $join->on('primary_account.id', 'opportunities.primary_account_id');
            })
            ->leftJoin('companies as end_user', function (JoinClause $join) {
                $join->on('end_user.id', 'opportunities.end_user_id');
            })
            ->leftJoin('contacts as primary_account_contact', function (JoinClause $join) {
                $join->on('primary_account_contact.id', 'opportunities.primary_account_contact_id');
            })
            ->orderByRaw("isnull({$opportunityModel->qualifyColumn('order_in_pipeline_stage')}) asc")
            ->orderBy($opportunityModel->qualifyColumn('order_in_pipeline_stage'))
            ->orderByDesc($opportunityModel->getQualifiedCreatedAtColumn());

        return RequestQueryBuilder::for($query, $request)
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->process();
    }

    public function opportunitiesOfPipelineStageQuery(PipelineStage $pipelineStage): Builder
    {
        $opportunityModel = new Opportunity();

        return $opportunityModel->newQuery()
            ->whereBelongsTo($pipelineStage)
            ->doesntHave('worldwideQuotes')
            ->where($opportunityModel->qualifyColumn('status'), OpportunityStatus::NOT_LOST);
    }
}
