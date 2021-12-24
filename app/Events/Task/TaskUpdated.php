<?php

namespace App\Events\Task;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated
{
    use Dispatchable, SerializesModels;

    public Task $task;

    public array $usersSyncResult;

    /**
     * Create a new event instance.
     *
     * @param Task $task
     * @param array $usersSyncResult
     */
    public function __construct(Task $task, array $usersSyncResult)
    {
        $this->task = $task;
        $this->usersSyncResult = $usersSyncResult;
    }
}
