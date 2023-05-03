<?php

namespace App\Domain\Task\Providers;

use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskReminder;
use App\Domain\Task\Policies\TaskPolicy;
use App\Domain\Task\Policies\TaskReminderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TaskAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskReminder::class, TaskReminderPolicy::class);
    }
}
