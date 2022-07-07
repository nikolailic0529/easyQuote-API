<?php

namespace App\Providers;

use App\Contracts\LoggerAware;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Services\Pipeliner\PipelinerDataSyncService;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use App\Services\Pipeliner\Strategies\SyncStrategyCollection;
use App\Services\Pipeliner\Webhook\EventHandlers\EventHandler;
use App\Services\Pipeliner\Webhook\EventHandlers\EventHandlerCollection;
use Illuminate\Support\ServiceProvider;

class PipelinerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving(PipelinerDataSyncService::class, function (PipelinerDataSyncService $concrete): void {
            if ($concrete instanceof LoggerAware) {
                $concrete->setLogger($this->app->make('log')->channel('pipeliner'));
            }
        });

        $this->app->afterResolving(PipelinerGraphQlClient::class, function (PipelinerGraphQlClient $concrete): void {
            if ($concrete instanceof LoggerAware) {
                $concrete->setLogger($this->app->make('log')->channel('pipeliner-requests'));
            }
        });

        $this->app->tag($this->app['config']['pipeliner.sync.strategies'], SyncStrategy::class);

        $this->app->when(SyncStrategyCollection::class)->needs(SyncStrategy::class)->giveTagged(SyncStrategy::class);

        $this->app->tag($this->app['config']['pipeliner.webhook.event_handlers'], EventHandler::class);

        $this->app->when(EventHandlerCollection::class)->needs(EventHandler::class)->giveTagged(EventHandler::class);
    }
}
