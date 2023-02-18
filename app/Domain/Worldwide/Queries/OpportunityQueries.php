<?php

namespace App\Domain\Worldwide\Queries;

use App\Domain\Authorization\Queries\Scopes\CurrentUserScope;
use App\Domain\Company\Models\Company;
use App\Domain\ContractType\Models\ContractType;
use App\Domain\Pipeline\Models\PipelineStage;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Enum\OpportunityStatus;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Database\Eloquent\QueryFilter\Enum\OperatorEnum;
use App\Foundation\Database\Eloquent\QueryFilter\Enum\PipeBooleanEnum;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\FilterFieldPipe;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\LikeValueProcessor;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PipeGroup;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
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
            ->with([
                'salesUnit' => static function (Relation $builder): void {
                    $salesUnit = new SalesUnit();

                    $builder->select([
                        $salesUnit->getQualifiedKeyName(),
                        $salesUnit->qualifyColumn('unit_name'),
                    ]);
                },
                'worldwideQuotes' => static function (Relation $builder): void {
                    $worldwideQuote = new WorldwideQuote();

                    $builder->select([
                        $worldwideQuote->getQualifiedKeyName(),
                        $worldwideQuote->user()->getQualifiedForeignKeyName(),
                        $worldwideQuote->opportunity()->getQualifiedForeignKeyName(),
                        $worldwideQuote->qualifyColumn('quote_number'),
                        $worldwideQuote->qualifyColumn('submitted_at'),
                    ]);
                },
                'worldwideQuotes.salesUnit' => static function (Relation $builder): void {
                    $salesUnit = new SalesUnit();

                    $builder->select([
                        $salesUnit->getQualifiedKeyName(),
                        $salesUnit->qualifyColumn('unit_name'),
                    ]);
                },
                'worldwideQuotes.user' => static function (Relation $builder): void {
                    $user = new User();

                    $builder->select([
                       $user->getQualifiedKeyName(),
                        ...$user->qualifyColumns([
                            'first_name',
                            'middle_name',
                            'last_name',
                            'email',
                            'user_fullname',
                        ]),
                    ]);
                },
            ])
            ->where('opportunities.status', OpportunityStatus::LOST);
    }

    public function listOkOpportunitiesQuery(Request $request): Builder
    {
        return $this->baseOpportunitiesQuery($request)
            ->withExists('worldwideQuotes as quotes_exist')
            ->with([
                'salesUnit' => static function (Relation $builder): void {
                    $salesUnit = new SalesUnit();

                    $builder->select([
                        $salesUnit->getQualifiedKeyName(),
                        $salesUnit->qualifyColumn('unit_name'),
                    ]);
                },
                'worldwideQuotes' => static function (Relation $builder): void {
                    $worldwideQuote = new WorldwideQuote();

                    $builder->select([
                        $worldwideQuote->getQualifiedKeyName(),
                        $worldwideQuote->user()->getQualifiedForeignKeyName(),
                        $worldwideQuote->opportunity()->getQualifiedForeignKeyName(),
                        $worldwideQuote->qualifyColumn('quote_number'),
                        $worldwideQuote->qualifyColumn('submitted_at'),
                    ]);
                },
                'worldwideQuotes.salesUnit' => static function (Relation $builder): void {
                    $salesUnit = new SalesUnit();

                    $builder->select([
                        $salesUnit->getQualifiedKeyName(),
                        $salesUnit->qualifyColumn('unit_name'),
                    ]);
                },
                'worldwideQuotes.user' => static function (Relation $builder): void {
                    $user = new User();

                    $builder->select([
                        $user->getQualifiedKeyName(),
                        ...$user->qualifyColumns([
                            'first_name',
                            'middle_name',
                            'last_name',
                            'email',
                            'user_fullname',
                        ]),
                    ]);
                },
            ])
            ->where('opportunities.status', OpportunityStatus::NOT_LOST);
    }

    public function listOpportunitiesOfCompanyQuery(Company $company, Request $request): Builder
    {
        return tap($this->baseOpportunitiesQuery($request), function (Builder $builder) use ($company) {
            $builder
                ->with([
                    'salesUnit' => static function (Relation $relation): void {
                        $relation->select([
                            $relation->getRelated()->getQualifiedKeyName(),
                            $relation->getRelated()->qualifyColumn('unit_name'),
                        ]);
                    },
                    'worldwideQuotes' => static function (Relation $relation): void {
                        $relation->select([
                            $relation->getRelated()->getQualifiedKeyName(),
                            (new WorldwideQuote())->user()->getQualifiedForeignKeyName(),
                            (new WorldwideQuote())->contractType()->getQualifiedForeignKeyName(),
                            (new WorldwideQuote())->opportunity()->getQualifiedForeignKeyName(),
                            $relation->getRelated()->qualifyColumn($relation->getParent()->getForeignKey()),
                            $relation->getRelated()->qualifyColumn('quote_number'),
                            $relation->getRelated()->qualifyColumn('submitted_at'),
                            $relation->getRelated()->getQualifiedCreatedAtColumn(),
                            $relation->getRelated()->getQualifiedUpdatedAtColumn(),
                        ])
                            ->with('user')
                            ->with('contractType')
                            ->with('salesUnit')
                            ->with(['salesOrder' => static function (Relation $relation): void {
                                $salesOrder = new SalesOrder();

                                $relation->select([
                                    $salesOrder->getQualifiedKeyName(),
                                    $salesOrder->user()->getQualifiedForeignKeyName(),
                                    $salesOrder->worldwideQuote()->getQualifiedForeignKeyName(),
                                    ...$salesOrder->qualifyColumns([
                                        'order_number',
                                        'order_date',
                                        'submitted_at',
                                    ]),
                                ])
                                    ->with('salesUnit');
                            }]);
                    },
                ])
                ->withExists('worldwideQuotes')
                ->where(static function (Builder $builder) use ($company): void {
                    $builder->whereBelongsTo($company, 'primaryAccount')
                        ->orWhereBelongsTo($company, 'endUser');
                });
        });
    }

    public function baseOpportunitiesOfCompanyQuery(Company $company): Builder
    {
        $oppModel = new Opportunity();

        return $oppModel->newQuery()
            ->where(static function (Builder $builder) use ($company): void {
                $builder->whereBelongsTo($company, 'primaryAccount')
                    ->orWhereBelongsTo($company, 'endUser');
            });
    }

    public function baseOpenOpportunitiesOfCompanyQuery(Company $company): Builder
    {
        return $this->baseOpportunitiesOfCompanyQuery($company)
            ->where('status', OpportunityStatus::NOT_LOST)
            ->whereDoesntHave('worldwideQuotes');
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
                $opportunityModel->primaryAccount()->getQualifiedForeignKeyName(),
                $opportunityModel->endUser()->getQualifiedForeignKeyName(),
                "{$opportunityModel->qualifyColumn('base_opportunity_amount')} as opportunity_amount",
                $opportunityModel->getQualifiedCreatedAtColumn(),
                ...$opportunityModel->qualifyColumns([
                    'project_name',
                    'opportunity_closing_date',
                    'opportunity_start_date',
                    'opportunity_end_date',
                    'status',
                    'status_reason',
                    'archived_at',
                ]),

                "{$pipelineStageModel->qualifyColumn('stage_name')} as sale_action_name",
                "{$userModel->qualifyColumn('user_fullname')} as account_manager_name",
                "{$contractTypeModel->qualifyColumn('type_short_name')} as opportunity_type",
                'primary_account.id as company_id',
                'primary_account.name as account_name',
                "{$unitModel->qualifyColumn('unit_name')} as unit_name",
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
                new class() implements RequestQueryBuilderPipe {
                    public function __invoke(BuildQueryParameters $parameters): void
                    {
                        [$builder, $request] = [$parameters->getBuilder(), $parameters->getRequest()];

                        $builder->where(static function (Builder $builder) use ($request): void {
                            $builder
                                ->unless(
                                    value: $request->boolean('include_archived') || $request->boolean('only_archived'),
                                    callback: static fn (Builder $builder): Builder => $builder->whereNull(
                                        $builder->qualifyColumn('archived_at')
                                    )
                                )
                                ->when(
                                    value: $request->boolean('only_archived'),
                                    callback: static fn (Builder $builder): Builder => $builder->whereNotNull(
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
        $contractTypeModel = new ContractType();

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
                "{$contractTypeModel->qualifyColumn('type_short_name')} as opportunity_type",

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

                new Expression('false as quotes_exist'),
            ])
            ->withOnly([
                'accountManager:id,email,user_fullname',
                'primaryAccount:id,name,phone,email',
                'primaryAccount.image',
                'endUser:id,name,phone,email',
                'endUser.image',
                'primaryAccountContact:id,first_name,last_name,phone,email',
                'validationResult:id,opportunity_id,messages,is_passed',
                'salesUnit' => static function (Relation $builder): void {
                    $salesUnit = new SalesUnit();

                    $builder->select([
                        $salesUnit->getQualifiedKeyName(),
                        $salesUnit->qualifyColumn('unit_name'),
                    ]);
                },
                'worldwideQuotes' => static function (Relation $builder): void {
                    $worldwideQuote = new WorldwideQuote();

                    $builder->select([
                        $worldwideQuote->getQualifiedKeyName(),
                        $worldwideQuote->user()->getQualifiedForeignKeyName(),
                        $worldwideQuote->opportunity()->getQualifiedForeignKeyName(),
                        $worldwideQuote->qualifyColumn('quote_number'),
                        $worldwideQuote->qualifyColumn('submitted_at'),
                    ]);
                },
                'worldwideQuotes.salesUnit' => static function (Relation $builder): void {
                    $salesUnit = new SalesUnit();

                    $builder->select([
                        $salesUnit->getQualifiedKeyName(),
                        $salesUnit->qualifyColumn('unit_name'),
                    ]);
                },
                'worldwideQuotes.user' => static function (Relation $builder): void {
                    $user = new User();

                    $builder->select([
                        $user->getQualifiedKeyName(),
                        ...$user->qualifyColumns([
                            'first_name',
                            'middle_name',
                            'last_name',
                            'email',
                            'user_fullname',
                        ]),
                    ]);
                },
            ])
            ->withExists('worldwideQuotes as quotes_exist')
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

    public function aggregateOpportunitiesOfMultiplePipelineStagesQuery(Collection $stages, Request $request): BaseBuilder
    {
        $opportunityModel = new Opportunity();

        return $this->baseOpportunitiesQuery($request)
            ->select([])
            ->reorder()
            ->leftJoin('opportunity_validation_results as ovr', 'ovr.opportunity_id', 'opportunities.id')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(base_opportunity_amount) as base_amount')
            ->selectRaw('sum(ovr.is_passed) as valid')
            ->addSelect([$opportunityModel->pipelineStage()->getQualifiedForeignKeyName().' as stage_id'])
            ->where($opportunityModel->qualifyColumn('status'), OpportunityStatus::NOT_LOST)
            ->whereIn($opportunityModel->pipelineStage()->getQualifiedForeignKeyName(), $stages->modelKeys())
            ->groupBy($opportunityModel->pipelineStage()->getQualifiedForeignKeyName())
            ->toBase();
    }
}
