<?php

namespace App\Policies;

use App\Models\{
    Task,
    User,
};
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quote tasks.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the quote task.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @param  Model|null $taskable
     * @return mixed
     */
    public function view(User $user, Task $task, ?Model $taskable = null)
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->id === $task->id) {
            return true;
        }

        if ($task->users->pluck('id')->contains($user->id)) {
            return true;
        }

        /** Allow view task if user is creator of the taskable model. */
        if ($taskable instanceof Model && $user->id === $taskable->user_id) {
            return true;
        }

        return $this->deny('You can not view this task.');
    }

    /**
     * Determine whether the user can create quote tasks.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the task.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return mixed
     */
    public function update(User $user, Task $task)
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->id === $task->user_id) {
            return true;
        }

        return $this->deny('You can not update this task.');
    }

    /**
     * Determine whether the user can delete the task.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return mixed
     */
    public function delete(User $user, Task $task)
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->id === $task->user_id) {
            return true;
        }

        return $this->deny('You can not delete this task.');
    }

    /**
     * Determine whether the user can restore the task.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return mixed
     */
    public function restore(User $user, Task $task)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the task.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return mixed
     */
    public function forceDelete(User $user, Task $task)
    {
        //
    }
}
