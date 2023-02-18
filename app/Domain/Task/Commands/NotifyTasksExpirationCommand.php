<?php

namespace App\Domain\Task\Commands;

use App\Domain\Task\Events\TaskExpired;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Queries\TaskQueries;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class NotifyTasksExpirationCommand extends Command
{
    const NOTIFICATION_KEY = 'expired';

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
     */
    public function handle(TaskQueries $queries): int
    {
        $tasks = $queries->expiredTasksQuery()
            ->whereDoesntHave('notifications', static function (Builder $query): void {
                $query->where('notification_key', static::NOTIFICATION_KEY);
            })
            ->lazyById(100);

        foreach ($tasks as $task) {
            $this->handleTask($task);
        }

        return self::SUCCESS;
    }

    private function handleTask(Task $task): void
    {
        event(new TaskExpired($task));

        $task->notifications()->create(['notification_key' => static::NOTIFICATION_KEY]);
    }
}
