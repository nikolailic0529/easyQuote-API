<?php

namespace App\Contracts\Repositories;

use App\Models\Task;
use Closure;
use Illuminate\Database\Eloquent\{
    Model,
    Collection,
    ModelNotFoundException,
};

interface TaskRepositoryInterface
{
    /**
     * Paginate existing quote tasks with specific clause.
     *
     * @param array $clause
     * @param string|null $search
     * @return mixed
     */
    public function paginate(array $clause = [], ?string $search = null);

    /**
     * Find the specified quote task by key.
     *
     * @param string $id
     * @return Task
     * @throws ModelNotFoundException
     */
    public function find(string $id): Task;

    /**
     * Get expired tasks.
     *
     * @param Closure|null $scope
     * @return Collection
     */
    public function getExpired(?Closure $scope = null): Collection;

    /**
     * Create a new quote task with specified attributes.
     *
     * @param array $attributes
     * @param Model $taskable
     * @return Task
     */
    public function create(array $attributes, Model $taskable): Task;

    /**
     * Update specified quote task with given attributes.
     *
     * @param string $id
     * @param array $attributes
     * @return Task
     */
    public function update(string $id, array $attributes): Task;

    /**
     * Sync assigned task users.
     * Returns an array with attached & detached users.
     *
     * @param Task $task
     * @param string[] $users
     * @return array
     */
    public function syncUsers(Task $task, array $users): array;

    /**
     * Delete soecified quote task.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;
}
