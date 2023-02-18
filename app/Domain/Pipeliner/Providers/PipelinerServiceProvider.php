<?php

namespace App\Domain\Pipeliner\Providers;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerGraphQlClient;
use App\Domain\Pipeliner\Jobs\QueuedPipelinerDataSync;
use App\Domain\Pipeliner\Jobs\SyncPipelinerEntity;
use App\Domain\Pipeliner\Services\PipelinerDataSyncService;
use App\Domain\Pipeliner\Services\PipelinerSyncAggregate;
use App\Domain\Pipeliner\Services\Strategies\Contracts\SyncStrategy;
use App\Domain\Pipeliner\Services\Strategies\SyncStrategyCollection;
use App\Domain\Pipeliner\Services\SyncPipelinerDataStatus;
use App\Domain\Pipeliner\Services\Webhook\EventHandlers\EventHandler;
use App\Domain\Pipeliner\Services\Webhook\EventHandlers\EventHandlerCollection;
use App\Domain\Settings\DatabaseSettingsStatus;
use App\Foundation\Http\Client\ConnectionLimiter\CacheStore as ConnLimiterCacheStore;
use App\Foundation\Http\Client\ConnectionLimiter\Store as ConnLimiterStore;
use App\Foundation\Http\Client\RateLimiter\CacheStore as RateLimiterCacheStore;
use App\Foundation\Http\Client\RateLimiter\Store as RateLimiterStore;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        $this->app->singleton(PipelinerSyncAggregate::class);

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
                return $container->make(RateLimiterCacheStore::class, ['prefix' => PipelinerGraphQlClient::class]);
            });

        $this->app->when(PipelinerGraphQlClient::class)
            ->needs(ConnLimiterStore::class)
            ->give(static function (Container $container): ConnLimiterStore {
                return $container->make(ConnLimiterCacheStore::class, ['prefix' => PipelinerGraphQlClient::class]);
            });
    }

    public function boot(): void
    {
        $this->app->resolving(Schedule::class, static function (Schedule $schedule, Container $container) {
            if (!$container[DatabaseSettingsStatus::class]->isEnabled()) {
                return;
            }

            $dispatcher = $container[Dispatcher::class];

            $frequency = filter_var(setting('pipeliner_sync_schedule') ?? 1, FILTER_SANITIZE_NUMBER_INT);

            $job = new QueuedPipelinerDataSync($aggregateId = Str::uuid()->toString(), owner: $owner = Str::random());

            $event = $schedule->call(function () use ($dispatcher, $job): void {
                $dispatcher->dispatch($job);
            })
                ->when(static function (Repository $config, SyncPipelinerDataStatus $status) use ($owner): bool {
                    return $config->get('pipeliner.sync.schedule.enabled')
                        && $status->setOwner($owner)->acquire();
                })
                ->before(static function (LogManager $logManager) use ($aggregateId): void {
                    $logManager
                        ->channel('pipeliner')
                        ->info('Scheduled pipeliner sync: starting', [
                            'aggregate_id' => $aggregateId,
                        ]);
                })
                ->after(static function (LogManager $logManager) use ($aggregateId): void {
                    $logManager
                        ->channel('pipeliner')
                        ->info('Scheduled pipeliner sync: finished', [
                            'aggregate_id' => $aggregateId,
                        ]);
                })
                ->description('Pipeliner sync')
                ->runInBackground()
                ->withoutOverlapping();

            if ($frequency > 1) {
                $event->cron("0 */$frequency * * *");
            } else {
                $event->cron('0 * * * *');
            }
        });
    }
}
