<?php

namespace App\Domain\Pipeline\Queries;

use App\Domain\Pipeline\Models\Pipeline as PipelineModel;
use App\Domain\Space\Models\Space;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PipelineQueries
{
    public function __construct()
    {
    }

    public function paginatePipelinesQuery(Request $request = null): Builder
    {
        $request ??= new Request();

        $spaceModel = new Space();
        $pipelineModel = new PipelineModel();

        $query = $pipelineModel->newQuery()
            ->select([
                $pipelineModel->getQualifiedKeyName(),
                "{$spaceModel->qualifyColumn('space_name')} as space_name",
                $pipelineModel->qualifyColumn('pipeline_name'),
                $pipelineModel->qualifyColumn('is_system'),
                $pipelineModel->qualifyColumn('is_default'),
                $pipelineModel->qualifyColumn('created_at'),
                $pipelineModel->qualifyColumn('updated_at'),
            ])
            ->join($spaceModel->getTable(), function (JoinClause $join) use ($spaceModel, $pipelineModel) {
                $join->on($spaceModel->getQualifiedKeyName(), $pipelineModel->space()->getQualifiedForeignKeyName());
            });

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request
        )
            ->allowOrderFields(...[
                'is_system',
                'is_default',
                'space_name',
                'created_at',
                'updated_at',
                'pipeline_name',
            ])
            ->qualifyOrderFields(
                is_system: $pipelineModel->qualifyColumn('is_system'),
                is_default: $pipelineModel->qualifyColumn('is_default'),
                space_name: $spaceModel->qualifyColumn('space_name'),
                created_at: $pipelineModel->qualifyColumn('created_at'),
                updated_at: $pipelineModel->qualifyColumn('updated_at'),
                pipeline_name: $pipelineModel->qualifyColumn('pipeline_name'),
            )
            ->enforceOrderBy($pipelineModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function defaultPipelinesQuery(): Builder
    {
        return PipelineModel::query()
            ->orderBy('is_default', 'desc');
    }

    public function explicitlyDefaultPipelinesQuery(): Builder
    {
        return PipelineModel::query()
            ->where('is_default', true);
    }

    public function pipelineListQuery(Request $request = null): Builder
    {
        $query = PipelineModel::query()
            ->select([
                'id',
                'space_id',
                'is_default',
                'pipeline_name',
            ])
            ->withExists('opportunityForm')
            ->orderBy('is_default', 'desc')
            ->orderBy('pipeline_order');

        return tap($query, function (Builder $query) use ($request): void {
            if ($request->has('filter.space_id')) {
                $query->whereIn('space_id', Arr::wrap($request->input('filter.space_id')));
            }
        });
    }

    public function pipelineWithoutOpportunityFormListQuery(): Builder
    {
        return PipelineModel::query()
            ->select([
                'id',
                'pipeline_name',
            ])
            ->whereDoesntHave('opportunityForm')
            ->orderBy('is_default', 'desc')
            ->orderBy('pipeline_order');
    }
}
