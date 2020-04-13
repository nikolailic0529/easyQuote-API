<?php

namespace App\Console\Commands\Routine\Notifications;

use App\Contracts\Repositories\TaskRepositoryInterface as Tasks;
use App\Models\Task;
use App\Events\Task\TaskExpired;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Closure;

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

    protected Tasks $tasks;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Tasks $tasks)
    {
        parent::__construct();

        $this->tasks = $tasks;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->tasks->getExpired(static::scope())
            ->each(fn (Task $task) => $this->handleTask($task));
    }

    protected function handleTask(Task $task)
    {
        event(new TaskExpired($task));

        $task->notifications()->create(['notification_key' => static::NOTIFICATION_KEY]);
    }

    protected static function scope(): Closure
    {
        return fn (Builder $query) =>
        $query
            ->whereHasMorph('taskable', Task::TASKABLES)
            ->whereDoesntHave(
                'notifications',
                fn (Builder $query) => $query->whereNotificationKey(static::NOTIFICATION_KEY)
            );
    }
}
