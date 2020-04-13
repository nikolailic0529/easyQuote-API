<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Repositories\Concerns\FiltersQuery;
use App\Models\Task;
use Illuminate\Database\Eloquent\{
    Builder,
    Model,
    Collection as DbCollection,
    Relations\MorphToMany,
};
use Arr, DB;
use Carbon\Carbon;
use Closure;

class TaskRepository implements TaskRepositoryInterface
{
    use FiltersQuery;

    protected Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function paginate(array $clause = [], ?string $search = null)
    {
        $query = $this->task->query()->with('user', 'users', 'attachments')->where($clause);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $this->filterQuery($query);

        return $query->apiPaginate();
    }

    public function find(string $id): Task
    {
        return $this->task->whereKey($id)->firstOrFail();
    }

    public function getExpired(?Closure $scope = null): DbCollection
    {
        return $this->task->query()
            ->when($scope instanceof Closure, fn (Builder $query) => $scope($query))
            ->where('expiry_date', '<', now())
            ->with('user', 'users')
            ->get();
    }

    public function create(array $attributes, Model $taskable): Task
    {
        $morph = [
            'taskable_id' => $taskable->id,
            'taskable_type' => $taskable->getMorphClass()
        ];

        $attributes = $morph + $attributes;

        return
            DB::transaction(
                fn () =>
                tap($this->task->create($attributes), function (Task $task) use ($attributes) {
                    $task->syncedUsers = $this->syncUsers($task, Arr::get($attributes, 'users') ?? []);
                    $task->syncedAttachments = $this->syncAttachments($task, Arr::get($attributes, 'attachments') ?? []);
                })
            );
    }

    public function update(string $id, array $attributes): Task
    {
        return
            DB::transaction(
                fn () =>
                tap($this->find($id), function (Task $task) use ($attributes) {
                    $this->handleExpiration($task, Arr::get($attributes, 'expiry_date'));

                    $task->syncedUsers = $this->syncUsers($task, Arr::get($attributes, 'users') ?? []);
                    $task->syncedAttachments = $this->syncAttachments($task, Arr::get($attributes, 'attachments') ?? []);

                    $task->update($attributes);
                })
            );
    }

    public function syncUsers(Task $task, array $users): array
    {
        return $task->users()->sync($users);
    }

    public function syncAttachments(Task $task, array $attachments): array
    {
        return $task->attachments()->sync($attachments);
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    protected function handleExpiration(Task $task, $time): void
    {
        $expiration = Carbon::parse($time);

        if ($expiration->ne($task->expiry_date)) {
            $task->notifications()->whereNotificationKey('expired')->delete();
        }
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
        ];
    }
}
