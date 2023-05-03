<?php

namespace App\Domain\Task\Providers;

use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Task\Services\TaskOwnershipService;
use Illuminate\Support\ServiceProvider;

class TaskOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(TaskOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}
