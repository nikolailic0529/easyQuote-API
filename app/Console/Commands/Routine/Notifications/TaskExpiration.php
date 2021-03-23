<?php

namespace App\Console\Commands\Routine\Notifications;

use App\Events\Task\TaskExpired;
use App\Models\Task;
use App\Queries\TaskQueries;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class TaskExpiration extends Command
{
    public const NOTIFICATION_KEY = 'expired';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:notify-tasks-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify task creator and task assigned users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param TaskQueries $queries
     * @return mixed
     */
    public function handle(TaskQueries $queries)
    {
        $tasks = $queries->expiredTasksQuery()
            ->whereHasMorph('taskable', Task::TASKABLES)
            ->whereDoesntHave(
                'notifications',
                fn (Builder $query) => $query->where('notification_key', static::NOTIFICATION_KEY)
            )
            ->get();

        $tasks->each(fn (Task $task) => $this->handleTask($task));
    }

    protected function handleTask(Task $task)
    {
        event(new TaskExpired($task));

        $task->notifications()->create(['notification_key' => static::NOTIFICATION_KEY]);
    }
}
