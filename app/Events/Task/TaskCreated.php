<?php

namespace App\Events\Task;

use App\Contracts\LinkedToTasks;
use App\Models\Task\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Task $task,
                                public readonly Model&LinkedToTasks $linkedModel)
    {
    }
}
