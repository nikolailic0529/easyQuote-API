<?php

namespace App\Queries;

use App\Models\Task;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TaskQueries
{
    public function paginateTaskableTasksQuery(string $taskableId, ?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Task();

        $query = $model->newQuery()
            ->where('taskable_id', $taskableId)
            ->with('user', 'users', 'attachments');

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

    public function expiredTasksQuery(): Builder
    {
        return Task::query()
            ->where('expiry_date', '<', now())
            ->with('user', 'users');
    }
}
