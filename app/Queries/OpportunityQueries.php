<?php

namespace App\Queries;

use App\Enum\OpportunityStatus;
use App\Models\Company;
use App\Models\ContractType;
use App\Models\Opportunity;
use App\Models\Pipeline\PipelineStage;
use App\Models\SalesUnit;
use App\Models\User;
use App\Queries\Enums\OperatorEnum;
use App\Queries\Enums\PipeBooleanEnum;
use App\Queries\Pipeline\FilterFieldPipe;
use App\Queries\Pipeline\LikeValueProcessor;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use App\Queries\Pipeline\PipeGroup;
use App\Queries\Scopes\CurrentUserScope;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class OpportunityQueries
{
    public function __construct(
        protected Elasticsearch $elasticsearch,
        protected Gate $gate
    ) {
    }

    public function listLostOpportunitiesQuery(Request $request): Builder
    {
        return $this->baseOpportunitiesQuery($request)
            ->withExists('worldwideQuotes as quotes_exist')
            ->with(['salesUnit:id,unit_name'])
            ->where('opportunities.status', OpportunityStatus::LOST);
    }

    public function listOkOpportunitiesQuery(Request $request): Builder
    {
        return $this->baseOpportunitiesQuery($request)
            ->withExists('worldwideQuotes as quotes_exist')
            ->with(['salesUnit:id,unit_name'])
            ->where('opportunities.status', OpportunityStatus::NOT_LOST);
    }

    public function listOpportunitiesOfCompanyQuery(Company $company, Request $request): Builder
    {
        return tap($this->baseOpportunitiesQuery($request), function (Builder $builder) use ($company) {
            $builder
                ->with(['salesUnit:id,unit_name'])
                ->withExists('worldwideQuotes')
                ->where(static function (Builder $builder) use ($company): void {
                    $builder->whereBelongsTo($company, 'primaryAccount')
                        ->orWhereBelongsTo($company, 'endUser');
                });
        });
    }

    public function baseOpportunitiesQuery(Request $request): Builder
    {
        $opportunityModel = new Opportunity();
        $pipelineStageModel = new PipelineStage();
        $contractTypeModel = new ContractType();
        $userModel = new User();
        $companyModel = new Company();
        $unitModel = new SalesUnit();

        $query = $opportunityModel->newQuery()
            ->select([
                $opportunityModel->getQualifiedKeyName(),
                $opportunityModel->salesUnit()->getQualifiedForeignKeyName(),
                $opportunityModel->owner()->getQualifiedForeignKeyName(),
                $opportunityModel->accountManager()->getQualifiedForeignKeyName(),
                "{$opportunityModel->qualifyColumn('base_opportunity_amount')} as opportunity_amount",
                $opportunityModel->getQualifiedCreatedAtColumn(),
                ...$opportunityModel->qualifyColumns([
                    'project_name',
                    'opportunity_closing_date',
                    'opportunity_start_date',
                    'opportunity_end_date',
                    'status',
                    'status_reason',
                ]),

                "{$pipelineStageModel->qualifyColumn('stage_name')} as sale_action_name",
                "{$userModel->qualifyColumn('user_fullname')} as account_manager_name",
                "{$contractTypeModel->qualifyColumn('type_short_name')} as opportunity_type",
                'primary_account.id as company_id',
                'primary_account.name as account_name',
                "{$unitModel->qualifyColumn('unit_name')} as unit_name"
            ])
            ->leftJoin(
                table: $unitModel->getTable(),
                first: $unitModel->getQualifiedKeyName(),
                operator: $opportunityModel->salesUnit()->getQualifiedForeignKeyName(),
            )
            ->leftJoin(
                table: $contractTypeModel->getTable(),
                first: $contractTypeModel->getQualifiedKeyName(),
                operator: $opportunityModel->contractType()->getQualifiedForeignKeyName(),
            )
            ->leftJoin(
                table: $pipelineStageModel->getTable(),
                first: $pipelineStageModel->getQualifiedKeyName(),
                operator: $opportunityModel->pipelineStage()->getQualifiedForeignKeyName()
            )
            ->leftJoin(
                table: $userModel->getTable(),
                first: $userModel->getQualifiedKeyName(),
                operator: $opportunityModel->accountManager()->getQualifiedForeignKeyName(),
            )
            ->leftJoin(
                table: "{$companyModel->getTable()} as primary_account",
                first: "primary_account.{$companyModel->getKeyName()}",
                operator: $opportunityModel->primaryAccount()->getQualifiedForeignKeyName(),
            )
            ->leftJoin(
                table: "{$companyModel->getTable()} as end_user",
                first: "end_user.{$companyModel->getKeyName()}",
                operator: $opportunityModel->endUser()->getQualifiedForeignKeyName(),
            )
            ->tap(CurrentUserScope::from($request, $this->gate));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new FilterFieldPipe(
                    field: 'sales_unit_id',
                    column: $opportunityModel->salesUnit()->getQualifiedForeignKeyName(),
                ),
                new FilterFieldPipe(
                    field: 'account_manager_id',
                    column: $opportunityModel->accountManager()->getQualifiedForeignKeyName(),
                ),
                PipeGroup::of(
                    new FilterFieldPipe(
                        field: 'customer_name',
                        column: 'primary_account.name',
                        operator: OperatorEnum::Like,
                        valueProcessor: LikeValueProcessor::new()
                    ),
                    new FilterFieldPipe(
                        field: 'customer_name',
                        column: 'end_user.name',
                        operator: OperatorEnum::Like,
                        valueProcessor: LikeValueProcessor::new(),
                    )
                )
                    ->boolean(PipeBooleanEnum::Or),
                new class implements RequestQueryBuilderPipe {

                    public function __invoke(BuildQueryParameters $parameters): void
                    {
                        [$builder, $request] = [$parameters->getBuilder(), $parameters->getRequest()];

                        $builder->where(static function (Builder $builder) use ($request): void {
                            $builder
                                ->unless(
                                    value: $request->boolean('include_archived') || $request->boolean('only_archived'),
                                    callback: static fn(Builder $builder): Builder => $builder->whereNull(
                                        $builder->qualifyColumn('archived_at')
                                    )
                                )
                                ->when(
                                    value: $request->boolean('only_archived'),
                                    callback: static fn(Builder $builder): Builder => $builder->whereNotNull(
                                        $builder->qualifyColumn('archived_at')
                                    )
                                );
                        });
                    }
                },
                new PerformElasticsearchSearch($this->elasticsearch),
            )
            ->allowOrderFields(
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
                'unit_name',
            )
            ->qualifyOrderFields(
                account_name: 'primary_account.name',
                project_name: $opportunityModel->qualifyColumn('project_name'),
                opportunity_type: 'contract_types.type_short_name',
                opportunity_amount: $opportunityModel->qualifyColumn('opportunities.base_opportunity_amount'),
                opportunity_start_date: $opportunityModel->qualifyColumn('opportunity_start_date'),
                opportunity_end_date: $opportunityModel->qualifyColumn('opportunity_end_date'),
                opportunity_closing_date: $opportunityModel->qualifyColumn('opportunity_closing_date'),
                sale_action_name: $opportunityModel->qualifyColumn('sale_action_name'),
                account_manager_name: 'users.user_fullname',
                status: $opportunityModel->qualifyColumn('status'),
                status_reason: $opportunityModel->qualifyColumn('status_reason'),
                created_at: $opportunityModel->getQualifiedCreatedAtColumn(),
                updated_at: $opportunityModel->getQualifiedUpdatedAtColumn(),
                unit_name: $unitModel->qualifyColumn('unit_name'),
            )
            ->enforceOrderBy($opportunityModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function listQuotedOpportunitiesQuery(Request $request): Builder
    {
        return $this->baseOpportunitiesQuery($request)
            ->addSelect([
                new Expression('0 as quotes_exist'),
            ])
            ->with(['salesUnit:id,unit_name'])
            ->doesntHave('worldwideQuotes');
    }

    public function paginateOpportunitiesOfPipelineStageQuery(PipelineStage $pipelineStage, Request $request): Builder
    {
        $opportunityModel = new Opportunity();

        return $this->opportunitiesOfPipelineStageQuery($pipelineStage, $request)
            ->select([
                $opportunityModel->getQualifiedKeyName(),
                $opportunityModel->salesUnit()->getQualifiedForeignKeyName(),
                $opportunityModel->pipelineStage()->getQualifiedForeignKeyName(),
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
            ->withOnly([
                'accountManager:id,email,user_fullname',
                'primaryAccount:id,name,phone,email',
                'primaryAccount.image',
                'endUser:id,name,phone,email',
                'endUser.image',
                'primaryAccountContact:id,first_name,last_name,phone,email',
                'salesUnit:id,unit_name',
                'validationResult:id,opportunity_id,messages,is_passed',
            ])
            ->leftJoin('contacts as primary_account_contact', function (JoinClause $join) {
                $join->on('primary_account_contact.id', 'opportunities.primary_account_contact_id');
            })
            ->orderByRaw("isnull({$opportunityModel->qualifyColumn('order_in_pipeline_stage')}) asc")
            ->orderBy($opportunityModel->qualifyColumn('order_in_pipeline_stage'))
            ->orderByDesc($opportunityModel->getQualifiedCreatedAtColumn());
    }

    public function opportunitiesOfPipelineStageQuery(PipelineStage $pipelineStage, Request $request): Builder
    {
        $opportunityModel = new Opportunity();

        return $this->baseOpportunitiesQuery($request)
            ->with(['salesUnit:id,unit_name'])
            ->whereBelongsTo($pipelineStage)
            ->withExists('worldwideQuotes as quotes_exist')
            ->where($opportunityModel->qualifyColumn('status'), OpportunityStatus::NOT_LOST);
    }
}
