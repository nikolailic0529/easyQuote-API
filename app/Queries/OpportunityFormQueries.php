<?php

namespace App\Queries;

use App\Helpers\ElasticsearchHelper;
use App\Http\Query\DefaultOrderBy;
use App\Http\Query\OrderByColumnName;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\Pipeline\Pipeline as PipelineModel;
use App\Models\Space;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class OpportunityFormQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
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
                $opportunityFormModel->getQualifiedUpdatedAtColumn()
            ])
            ->join($pipelineModel->getTable(), function (JoinClause $join) use ($opportunityFormModel, $pipelineModel) {
                $join->on($pipelineModel->getQualifiedKeyName(), $opportunityFormModel->pipeline()->getQualifiedForeignKeyName());
            })
            ->join($spaceModel->getTable(), function (JoinClause $join) use ($pipelineModel, $opportunityFormModel, $spaceModel) {
                $join->on($spaceModel->getQualifiedKeyName(), $pipelineModel->space()->getQualifiedForeignKeyName());
            });

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($opportunityFormModel, $searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex($opportunityFormModel)
                        ->queryString('*'.ElasticsearchHelper::escapeReservedChars($searchQuery).'*')
                        ->toArray()
                );
            });

            $query->whereKey(ElasticsearchHelper::pluckDocumentKeys($hits));
        }

        return $this->pipeline
            ->send($query)
            ->through([
                new OrderByColumnName($request, $spaceModel->qualifyColumn('space_name'), 'space_name'),
                new OrderByColumnName($request, $pipelineModel->qualifyColumn('pipeline_name'), 'pipeline_name'),
                new OrderByColumnName($request, $opportunityFormModel->qualifyColumn('created_at'), 'created_at'),
                new OrderByColumnName($request, $opportunityFormModel->qualifyColumn('updated_at'), 'updated_at'),
                new DefaultOrderBy($opportunityFormModel->qualifyColumn('created_at'))
            ])
            ->thenReturn();
    }
}
