<?php

namespace App\Providers;

use App\Repositories\TaskTemplate\QuoteTaskTemplateStore;
use App\Repositories\TaskTemplate\TaskTemplateManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(QuoteTaskTemplateStore::class, fn() => QuoteTaskTemplateStore::make(storage_path('valuestore/quote.task.template.json')));

        $this->app->tag([QuoteTaskTemplateStore::class], 'task_templates');

        $this->app->bind('task_template.manager', fn($app) => new TaskTemplateManager($app->tagged('task_templates')));
    }

    public function provides()
    {
        return [
            QuoteTaskTemplateStore::class,
            'task_template.manager'
        ];
    }
}
