<?php

namespace App\Queries;

use App\Models\System\Activity;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function filteredActivityQuery(): Builder
    {
        $model = new Activity();

        $query = $model->newQuery()
            ->with('subject');

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\Activity\Types::class,
                \App\Http\Query\Activity\Period::class,
                \App\Http\Query\Activity\CustomPeriod::class,
                \App\Http\Query\Activity\CauserId::class,
                \App\Http\Query\Activity\SubjectTypes::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }

    public function filteredActivityBySubjectQuery(string $subjectId): Builder
    {
        $model = new Activity();

        $query = $model->newQuery()
            ->with('subject')
            ->where('subject_id', $subjectId);

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\Activity\Types::class,
                \App\Http\Query\Activity\Period::class,
                \App\Http\Query\Activity\CustomPeriod::class,
                \App\Http\Query\Activity\CauserId::class,
                \App\Http\Query\Activity\SubjectTypes::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }

    public function paginateActivityQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Activity();

        $query = $model->newQuery()
            ->with('subject');

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($model, $searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery())
                        ->modelIndex($model)
                        ->queryString(Str::of($searchQuery)->start('*')->finish('*'))
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\Activity\Types::class,
                \App\Http\Query\Activity\Period::class,
                \App\Http\Query\Activity\CustomPeriod::class,
                \App\Http\Query\Activity\CauserId::class,
                \App\Http\Query\Activity\SubjectTypes::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }

    public function activitySummaryQuery(): BaseBuilder
    {
        $query = Activity::query()
            ->toBase()
            ->selectRaw('count(*) as count')
            ->select([
                'description as type',
                DB::raw('count(*) as count')
            ])
            ->groupBy('description');

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\Activity\Types::class,
                \App\Http\Query\Activity\Period::class,
                \App\Http\Query\Activity\CustomPeriod::class,
                \App\Http\Query\Activity\CauserId::class,
                \App\Http\Query\Activity\SubjectTypes::class,
            ])
            ->thenReturn();
    }

    public function activitySummaryBySubjectQuery(string $subject): BaseBuilder
    {
        $query = Activity::query()
            ->where('subject_id', $subject)
            ->toBase()
            ->selectRaw('count(*) as count')
            ->select([
                'description as type',
                DB::raw('count(*) as count')
            ])
            ->groupBy('description');

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\Activity\Types::class,
                \App\Http\Query\Activity\Period::class,
                \App\Http\Query\Activity\CustomPeriod::class,
                \App\Http\Query\Activity\CauserId::class,
                \App\Http\Query\Activity\SubjectTypes::class,
            ])
            ->thenReturn();
    }

    public function paginateActivityBySubjectQuery(string $subjectId, ?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Activity();

        $query = $model->newQuery()
            ->with('subject')
            ->where('subject_id', $subjectId);

        if (filled($searchQuery = $request->query('search'))) {
            $hits = rescue(function () use ($model, $searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery())
                        ->modelIndex($model)
                        ->queryString(Str::of($searchQuery)->start('*')->finish('*'))
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);
        }

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\Activity\Types::class,
                \App\Http\Query\Activity\Period::class,
                \App\Http\Query\Activity\CustomPeriod::class,
                \App\Http\Query\Activity\CauserId::class,
                \App\Http\Query\Activity\SubjectTypes::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }
}
