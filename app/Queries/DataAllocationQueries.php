<?php

namespace App\Queries;

use App\Enum\DataAllocationStageEnum;
use App\Models\DataAllocation\DataAllocation;
use App\Queries\Enums\OperatorEnum;
use App\Queries\Pipeline\FilterFieldPipe;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DataAllocationQueries
{
    public function listDataAllocationsQuery(Request $request = new Request()): Builder
    {
        $model = new DataAllocation();
        $companyModel = $model->company()->getModel();
        $divisionModel = $model->businessDivision()->getModel();

        $builder = $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->owner()->getQualifiedForeignKeyName(),
                ...$model->qualifyColumns([
                    'distribution_algorithm',
                    'assignment_start_date',
                    'assignment_end_date',
                    'stage',
                ]),
                "{$companyModel->qualifyColumn('name')} as company_name",
                "{$divisionModel->qualifyColumn('division_name')} as division_name",
                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
            ])
            ->leftJoin($companyModel->getTable(), $companyModel->getQualifiedKeyName(),
                $model->company()->getQualifiedForeignKeyName())
            ->leftJoin($divisionModel->getTable(), $divisionModel->getQualifiedKeyName(),
                $model->businessDivision()->getQualifiedForeignKeyName());

        return RequestQueryBuilder::for(
            $builder, $request
        )
            ->allowOrderFields(
                'company_name',
                'division_name',
                'distribution_algorithm',
                'assignment_start_date',
                'assignment_end_date',
                'stage',
                'created_at',
                'updated_at',
            )
            ->qualifyOrderFields(
                company_name: $companyModel->qualifyColumn('name'),
                division_name: $divisionModel->qualifyColumn('division_name'),
                distribution_algorithm: $model->qualifyColumn('distribution_algorithm'),
                assignment_start_date: $model->qualifyColumn('assignment_start_date'),
                assignment_end_date: $model->qualifyColumn('assignment_end_date'),
                stage: $model->qualifyColumn('stage'),
                created_at: $model->getQualifiedCreatedAtColumn(),
                updated_at: $model->getQualifiedUpdatedAtColumn(),
            )
            ->addCustomBuildQueryPipe(
            // assignment start date filters
                new FilterFieldPipe(
                    'gt.assignment_start_date',
                    $model->qualifyColumn('assignment_start_date'),
                    OperatorEnum::Gt
                ),
                new FilterFieldPipe(
                    'gte.assignment_start_date',
                    $model->qualifyColumn('assignment_start_date'),
                    OperatorEnum::Gte
                ),
                new FilterFieldPipe(
                    'lt.assignment_start_date',
                    $model->qualifyColumn('assignment_start_date'),
                    OperatorEnum::Lt
                ),
                new FilterFieldPipe(
                    'lte.assignment_start_date',
                    $model->qualifyColumn('assignment_start_date'),
                    OperatorEnum::Lte
                ),
                // assignment end date filters
                new FilterFieldPipe(
                    'gt.assignment_end_date',
                    $model->qualifyColumn('assignment_end_date'),
                    OperatorEnum::Gt
                ),
                new FilterFieldPipe(
                    'gte.assignment_end_date',
                    $model->qualifyColumn('assignment_end_date'),
                    OperatorEnum::Gte
                ),
                new FilterFieldPipe(
                    'lt.assignment_end_date',
                    $model->qualifyColumn('assignment_end_date'),
                    OperatorEnum::Lt
                ),
                new FilterFieldPipe(
                    'lte.assignment_end_date',
                    $model->qualifyColumn('assignment_end_date'),
                    OperatorEnum::Lte
                ),
                // created at filters
                new FilterFieldPipe(
                    'gt.created_at',
                    $model->getQualifiedCreatedAtColumn(),
                    OperatorEnum::Gt
                ),
                new FilterFieldPipe(
                    'gte.created_at',
                    $model->getQualifiedCreatedAtColumn(),
                    OperatorEnum::Gte
                ),
                new FilterFieldPipe(
                    'lt.created_at',
                    $model->getQualifiedCreatedAtColumn(),
                    OperatorEnum::Lt
                ),
                new FilterFieldPipe(
                    'lte.created_at',
                    $model->getQualifiedCreatedAtColumn(),
                    OperatorEnum::Lte
                ),
                // stage filter
                (new FilterFieldPipe(
                    'stage',
                    $model->qualifyColumn('stage'),
                ))
                    ->processValueWith(static function (mixed $value): array {
                        return collect($value)
                            ->lazy()
                            ->filter(static fn(mixed $name): bool => is_string($name))
                            ->map(static function (string $name): DataAllocationStageEnum {
                                return DataAllocationStageEnum::tryFromName($name);
                            })
                            ->all();
                    })
            )
            ->allowQuickSearchFields(
                $companyModel->qualifyColumn('name'),
                $divisionModel->qualifyColumn('division_name'),
                $model->qualifyColumn('distribution_algorithm'),
                $model->qualifyColumn('assignment_start_date'),
                $model->qualifyColumn('assignment_end_date'),
                $model->qualifyColumn('stage'),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}