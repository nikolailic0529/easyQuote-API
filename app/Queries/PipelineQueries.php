<?php

namespace App\Queries;

use App\Http\Query\DefaultOrderBy;
use App\Http\Query\OrderByColumnName;
use App\Models\Pipeline\Pipeline as PipelineModel;
use App\Models\Space;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;

class PipelineQueries
{
    protected Pipeline $pipeline;

    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
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

        return $this->pipeline
            ->send($query)
            ->through([
                new OrderByColumnName($request, $pipelineModel->qualifyColumn('is_system'), 'is_system'),
                new OrderByColumnName($request, $pipelineModel->qualifyColumn('is_default'), 'is_default'),
                new OrderByColumnName($request, $spaceModel->qualifyColumn('space_name'), 'space_name'),
                new OrderByColumnName($request, $pipelineModel->qualifyColumn('created_at'), 'created_at'),
                new OrderByColumnName($request, $pipelineModel->qualifyColumn('updated_at'), 'updated_at'),
                new OrderByColumnName($request, $pipelineModel->qualifyColumn('pipeline_name'), 'pipeline_name'),
                DefaultOrderBy::class,
            ])
            ->thenReturn();
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
                'pipeline_name'
            ])
            ->orderBy('is_default', 'desc')
            ->orderBy('pipeline_order');

        return tap($query, function (Builder $query) use ($request) {

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
                'pipeline_name'
            ])
            ->whereDoesntHave('opportunityForm')
            ->orderBy('is_default', 'desc')
            ->orderBy('pipeline_order');
    }
}
