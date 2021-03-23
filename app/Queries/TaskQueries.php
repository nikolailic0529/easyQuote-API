<?php

namespace App\Queries;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class TaskQueries
{
    protected Pipeline $pipeline;

    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function paginateTaskableTasksQuery(string $taskableId, ?Request $request = null): Builder
    {
        $request ??= new Request();

        $query = Task::where('taskable_id', $taskableId)
            ->with('user', 'users', 'attachments')
            ->when(filled($request->query('search')), function (Builder $builder) use ($request) {
                $input = $request->query('search');

                $builder->where('name', 'like', "%$input%");
            });

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();
    }

    public function expiredTasksQuery(): Builder
    {
        return Task::where('expiry_date', '<', now())
            ->with('user', 'users');
    }
}
