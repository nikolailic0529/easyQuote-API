<?php

namespace App\Queries;

use App\Models\User;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class UserQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function userListQuery(): Builder
    {
        return User::query()
            ->select([
                'id', 'first_name', 'middle_name', 'last_name', 'email',
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
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
                'email',
                'name',
                'first_name',
                'last_name',
                'role',
                'team_name',
            ])
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
                name: $model->qualifyColumn('user_fullname'),
                email: $model->qualifyColumn('email'),
                first_name: $model->qualifyColumn('first_name'),
                last_name: $model->qualifyColumn('last_name'),
                role: 'roles.name',
                team_name: 'teams.team_name',
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
