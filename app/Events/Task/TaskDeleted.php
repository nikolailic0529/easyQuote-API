<?php

namespace App\Events\Task;

use App\Models\Task\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted
{
    use Dispatchable, SerializesModels;

    public Task $task;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Task\Task $task
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}
