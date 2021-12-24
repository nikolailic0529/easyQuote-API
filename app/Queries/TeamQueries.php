<?php

namespace App\Queries;

use App\Models\Team;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TeamQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function listingQuery(): Builder
    {
        return Team::query()
            ->select([
                'id',
                'team_name',
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
                'created_at',
            ]);

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
                'team_name',
                'monthly_goal_amount',
            ])
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
