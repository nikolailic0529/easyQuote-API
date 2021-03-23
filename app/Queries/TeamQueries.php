<?php

namespace App\Queries;

use App\Models\Team;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class TeamQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function listingQuery(): Builder
    {
        return Team::query()
            ->select([
                'id',
                'team_name'
            ])
            ->orderBy('team_name');
    }

    public function paginateTeamsQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Team;

        $query = $model->query()
            ->select([
                'id',
                'team_name',
                'monthly_goal_amount',
                'is_system',
                'created_at'
            ]);

        if (filled($searchQuery = $request->query('search'))) {

            $hits = rescue(function () use ($model, $searchQuery) {
                return $this->elasticsearch->search(
                    (new ElasticsearchQuery)
                        ->modelIndex($model)
                        ->queryString('*'.trim($searchQuery, '*').'*')
                        ->toArray()
                );
            });

            $query->whereKey(data_get($hits, 'hits.hits.*._id') ?? []);

        }

        return $this->pipeline->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByTeamName::class,
                \App\Http\Query\OrderByMonthlyGoalAmount::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();

    }
}
