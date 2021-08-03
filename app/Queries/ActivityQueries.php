<?php

namespace App\Queries;

use App\Http\Query\{Activity\CauserId as FilterByCauserId,
    Activity\CustomPeriod as FilterByCustomPeriod,
    Activity\Period as FilterByPeriod,
    Activity\SubjectTypes as FilterBySubjectEntityTypes,
    Activity\Types as FilterByType,
    DefaultOrderBy,
    OrderByCreatedAt};
use App\Models\System\Activity;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class ActivityQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function paginateActivitiesQuery(Request $request = null)
    {
        $request ??= new Request();

        $model = (new Activity());

        $query = Activity::query()
            ->select([
                "{$model->getQualifiedKeyName()} as id",
                "{$model->subject()->getForeignKeyName()} as subject_id",
                "{$model->subject()->getMorphType()} as subject_type",
                "{$model->qualifyColumn('description')} as description",
                "users.user_fullname as causer_name",
                "{$model->qualifyColumn('causer_service')} as causer_service_name",
                "{$model->qualifyColumn('properties')} as properties",
                "{$model->getQualifiedCreatedAtColumn()} as created_at",
            ])
            ->leftJoin('users', function (JoinClause $join) use ($model) {
                $join->on('users.id', $model->causer()->getQualifiedForeignKeyName());
            });

        if (filled($searchQuery = $request->input('search'))) {
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
                OrderByCreatedAt::class,
                FilterByType::class,
                FilterByPeriod::class,
                FilterByCustomPeriod::class,
                FilterByCauserId::class,
                FilterBySubjectEntityTypes::class,
                new DefaultOrderBy($model->getQualifiedCreatedAtColumn()),
            ])
            ->thenReturn();
    }

    public function paginateActivitiesOfSubjectQuery(string $subject, Request $request = null)
    {
        $request ??= new Request();

        $model = (new Activity());

        $query = Activity::query()
            ->select([
                "{$model->getQualifiedKeyName()} as id",
                "{$model->subject()->getForeignKeyName()} as subject_id",
                "{$model->subject()->getMorphType()} as subject_type",
                "{$model->qualifyColumn('description')} as description",
                "users.user_fullname as causer_name",
                "{$model->qualifyColumn('causer_service')} as causer_service_name",
                "{$model->qualifyColumn('properties')} as properties",
                "{$model->getQualifiedCreatedAtColumn()} as created_at",
            ])
            ->leftJoin('users', function (JoinClause $join) use ($model) {
                $join->on('users.id', $model->causer()->getQualifiedForeignKeyName());
            })
            ->where($model->qualifyColumn('subject_id'), $subject);

        if (filled($searchQuery = $request->input('search'))) {
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
                OrderByCreatedAt::class,
                FilterByType::class,
                FilterByPeriod::class,
                FilterByCustomPeriod::class,
                FilterByCauserId::class,
                FilterBySubjectEntityTypes::class,
                new DefaultOrderBy($model->getQualifiedCreatedAtColumn()),
            ])
            ->thenReturn();
    }
}
