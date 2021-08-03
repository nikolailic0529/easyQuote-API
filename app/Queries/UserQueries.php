<?php

namespace App\Queries;

use App\Http\Query\OrderByTeamName;
use App\Models\User;
use App\Services\ElasticsearchQuery;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class UserQueries
{
    protected Pipeline $pipeline;

    protected Elasticsearch $elasticsearch;

    public function __construct(Pipeline $pipeline, Elasticsearch $elasticsearch)
    {
        $this->pipeline = $pipeline;
        $this->elasticsearch = $elasticsearch;
    }

    public function userListQuery(): Builder
    {
        return User::query()
            ->select([
                'id', 'first_name', 'middle_name', 'last_name', 'email'
            ]);
    }

    public function paginateUsersQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new User;

        $query = $model
            ->newQuery()
            ->with(['roles', 'image'])
            ->leftJoin('teams', function (JoinClause $join) {
                $join->on('teams.id', 'users.team_id');
            })
            ->leftJoin('roles', function (JoinClause $join) {
                $join->where('roles.id', function (BaseBuilder $builder) {
                    $builder->select('role_id')
                        ->from('model_has_roles')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->limit(1);
                });
            })
            ->select(['users.*', 'teams.team_name as team_name', 'roles.name as role_name'])
        ;

        if (filled($searchQuery = $request->query('search'))) {

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
                new \App\Http\Query\ActiveFirst('users.is_active'),
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\OrderByTeamName::class,
                \App\Http\Query\User\OrderByEmail::class,
                \App\Http\Query\User\OrderByName::class,
                \App\Http\Query\User\OrderByFirstname::class,
                \App\Http\Query\User\OrderByLastname::class,
                \App\Http\Query\User\OrderByRole::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }
}
