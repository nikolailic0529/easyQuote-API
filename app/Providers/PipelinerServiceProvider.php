<?php

namespace App\Providers;

use App\Contracts\LoggerAware;
use App\Foundation\Http\Client\RateLimiter\CacheStore;
use App\Foundation\Http\Client\RateLimiter\Store as RateLimiterStore;
use App\Foundation\Settings\DatabaseSettingsStatus;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Jobs\Pipeliner\QueuedPipelinerDataSync;
use App\Jobs\Pipeliner\SyncPipelinerEntity;
use App\Services\Pipeliner\PipelinerDataSyncService;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use App\Services\Pipeliner\Strategies\SyncStrategyCollection;
use App\Services\Pipeliner\SyncPipelinerDataStatus;
use App\Services\Pipeliner\Webhook\EventHandlers\EventHandler;
use App\Services\Pipeliner\Webhook\EventHandlers\EventHandlerCollection;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Log\LogManager;
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
        $this->app->singleton(SyncPipelinerDataStatus::class);

        $this->app->afterResolving(PipelinerDataSyncService::class,
            function (PipelinerDataSyncService $concrete): void {
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

        $this->app->bindMethod([SyncPipelinerEntity::class, 'handle'],
            static function (SyncPipelinerEntity $concrete, Container $container): mixed {
                return $container->call($concrete->handle(...), ['logger' => $container['log']->driver('pipeliner')]);
            });

        $this->app->when(PipelinerGraphQlClient::class)
            ->needs(RateLimiterStore::class)
            ->give(static function (Container $container): RateLimiterStore {
                return $container->make(CacheStore::class, ['prefix' => PipelinerGraphQlClient::class]);
            });
    }

    public function boot(): void
    {
        $this->app->resolving(Schedule::class, static function (Schedule $schedule, Container $container) {
            if (!$container[DatabaseSettingsStatus::class]->isEnabled()) {
                return;
            }

            $frequency = filter_var(setting('pipeliner_sync_schedule') ?? 1, FILTER_SANITIZE_NUMBER_INT);

            $event = $schedule->job(new QueuedPipelinerDataSync())
                ->when(static function (Repository $config, SyncPipelinerDataStatus $status): bool {
                    return $config->get('pipeliner.sync.schedule.enabled')
                        && $status->acquire();
                })
                ->before(static function (LogManager $logManager): void {
                    $logManager
                        ->channel('pipeliner')
                        ->info('Scheduled pipeliner sync: starting');
                })
                ->after(static function (LogManager $logManager): void {
                    $logManager
                        ->channel('pipeliner')
                        ->info('Scheduled pipeliner sync: finished');
                })
                ->description("Pipeliner sync")
                ->runInBackground()
                ->withoutOverlapping();

            if ($frequency > 1) {
                $event->cron("0 */$frequency * * *");
            } else {
                $event->cron("0 * * * *");
            }
        });
    }
}
