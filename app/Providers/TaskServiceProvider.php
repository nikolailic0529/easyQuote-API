<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Repositories\TaskRepository;
use App\Repositories\TaskTemplate\QuoteTaskTemplateStore;
use App\Repositories\TaskTemplate\TaskTemplateManager;

class TaskServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TaskRepositoryInterface::class, TaskRepository::class);

        $this->app->singleton(QuoteTaskTemplateStore::class, fn () => QuoteTaskTemplateStore::make(storage_path('valuestore/quote.task.template.json')));

        $this->app->tag([QuoteTaskTemplateStore::class], 'task_templates');

        $this->app->bind('task_template.manager', fn ($app) => new TaskTemplateManager($app->tagged('task_templates')));

        $this->app->alias(TaskRepositoryInterface::class, 'task.repository');
    }

    public function provides()
    {
        return [
            QuoteTaskTemplateStore::class,
            TaskRepositoryInterface::class,
            'task_template.manager',
            'task.repository',
        ];
    }
}
