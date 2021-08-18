<?php

namespace App\Queries;

use App\Models\OpportunityForm\OpportunityForm;
use App\Models\Pipeline\Pipeline as PipelineModel;
use App\Models\Space;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class OpportunityFormQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function paginateOpportunityFormsQuery(Request $request = null): Builder
    {
        $request ??= new Request();

        $opportunityFormModel = new OpportunityForm();
        $pipelineModel = new PipelineModel();
        $spaceModel = new Space();

        $query = $opportunityFormModel->newQuery()
            ->select([
                $opportunityFormModel->getQualifiedKeyName(),
                "{$spaceModel->qualifyColumn('space_name')} as space_name",
                "{$pipelineModel->qualifyColumn('pipeline_name')} as pipeline_name",
                $opportunityFormModel->getQualifiedCreatedAtColumn(),
                $opportunityFormModel->getQualifiedUpdatedAtColumn(),
            ])
            ->join($pipelineModel->getTable(), function (JoinClause $join) use ($opportunityFormModel, $pipelineModel) {
                $join->on($pipelineModel->getQualifiedKeyName(), $opportunityFormModel->pipeline()->getQualifiedForeignKeyName());
            })
            ->join($spaceModel->getTable(), function (JoinClause $join) use ($pipelineModel, $opportunityFormModel, $spaceModel) {
                $join->on($spaceModel->getQualifiedKeyName(), $pipelineModel->space()->getQualifiedForeignKeyName());
            });

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'space_name',
                'pipeline_name',
                'created_at',
                'updated_at',
            ])
            ->qualifyOrderFields(
                space_name: $spaceModel->qualifyColumn('space_name'),
                pipeline_name: $pipelineModel->qualifyColumn('pipeline_name'),
                created_at: $opportunityFormModel->getQualifiedCreatedAtColumn(),
                updated_at: $opportunityFormModel->getQualifiedUpdatedAtColumn(),
            )
            ->enforceOrderBy($opportunityFormModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
