<?php

namespace App\Queries;

use App\Models\ModelHasTasks;
use App\Models\Task\Task;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class TaskQueries
{
    public function paginateTaskableTasksQuery(string $taskableId, ?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Task();
        $modelHasTasksPivot = new ModelHasTasks();

        $query = $model->newQuery()
            ->with(['user', 'users', 'attachments'])
            ->join($modelHasTasksPivot->getTable(), function (JoinClause $join) use ($taskableId, $modelHasTasksPivot, $model): void {
                $join->on($modelHasTasksPivot->task()->getQualifiedForeignKeyName(), $model->getQualifiedKeyName())
                    ->where($modelHasTasksPivot->related()->getQualifiedForeignKeyName(), $taskableId);
            });

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->allowOrderFields(...[
                'created_at',
            ])
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
            )
            ->allowQuickSearchFields(...[
                'name',
            ])
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function listTasksOfTaskableQuery(string $taskableId, ?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Task();
        $modelHasTasksPivot = new ModelHasTasks();

        $query = $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $modelHasTasksPivot->related()->getQualifiedForeignKeyName(),
                $modelHasTasksPivot->related()->qualifyColumn($modelHasTasksPivot->related()->getMorphType()),
                $model->user()->getQualifiedForeignKeyName(),
                ...$model->qualifyColumns([
                    'activity_type',
                    'name',
                    'priority',
                    'expiry_date',
                    'priority',
                ]),
                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
            ])
            ->with(['user:id,user_fullname,email', 'users:id,user_fullname,email'])
            ->join($modelHasTasksPivot->getTable(), function (JoinClause $join) use ($taskableId, $modelHasTasksPivot, $model): void {
                $join->on($modelHasTasksPivot->task()->getQualifiedForeignKeyName(), $model->getQualifiedKeyName())
                    ->where($modelHasTasksPivot->related()->getQualifiedForeignKeyName(), $taskableId);
            });

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->allowOrderFields(
                'activity_type',
                'created_at'
            )
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
            )
            ->allowQuickSearchFields(
                'name'
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn())
            ->process();
    }

    public function expiredTasksQuery(): Builder
    {
        return Task::query()
            ->where('expiry_date', '<', now())
            ->with('user', 'users');
    }
}
