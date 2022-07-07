<?php

namespace App\Events\Task;

use App\Models\Task\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Task\Task $task
     * @param array $usersSyncResult
     */
    public function __construct(public readonly Task $task,
                                public readonly array $usersSyncResult)
    {
    }
}
