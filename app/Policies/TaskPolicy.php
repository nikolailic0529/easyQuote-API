<?php

namespace App\Policies;

use App\Contracts\HasOwner;
use App\Models\{Task\Task, User,};
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any quote tasks.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the quote task.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Task\Task $task
     * @param Model|null $taskable
     * @return mixed
     */
    public function view(User $user, Task $task)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($task->user()->is($user)) {
            return true;
        }

        if ($task->users->contains($user)) {
            return true;
        }

        return $this->deny('You can not view the task.');
    }

    /**
     * Determine whether the user can create quote tasks.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the task.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Task\Task $task
     * @return mixed
     */
    public function update(User $user, Task $task)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($task->user()->is($user)) {
            return true;
        }

        return $this->deny('You can not update the task.');
    }

    /**
     * Determine whether the user can delete the task.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Task\Task $task
     * @return mixed
     */
    public function delete(User $user, Task $task)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($task->user()->is($user)) {
            return true;
        }

        return $this->deny('You can not delete the task.');
    }
}
