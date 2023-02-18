<?php

namespace App\Domain\Task\Events;

use App\Domain\Task\Contracts\LinkedToTasks;
use App\Domain\Task\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Task $task,
                                public readonly Model&LinkedToTasks $linkedModel)
    {
    }
}
