<?php

namespace App\Domain\Authorization\Queries;

use App\Domain\Authorization\Models\Role;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RoleQueries
{
    public function __construct(
        protected readonly Client $elasticsearch
    ) {
    }

    public function listRolesQuery(): Builder
    {
        $model = new Role();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->qualifyColumn('name'),
                $model->qualifyColumn('is_system'),
                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
                $model->qualifyColumn('activated_at'),
            ])
            ->withCount('users');
    }

    public function paginateRolesQuery(Request $request = new Request()): Builder
    {
        $builder = $this->listRolesQuery();
        $model = $builder->getModel();
        $builder->orderByDesc($builder->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $builder,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(
                'name',
                'created_at',
                'users_count'
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
