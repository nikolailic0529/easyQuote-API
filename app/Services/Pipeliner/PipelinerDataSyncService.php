<?php

namespace App\Services\Pipeliner;

use App\Contracts\CauserAware;
use App\Contracts\CorrelationAware;
use App\Contracts\FlagsAware;
use App\Contracts\LoggerAware;
use App\Events\Pipeliner\QueuedPipelinerSyncLocalEntitySkipped;
use App\Events\Pipeliner\QueuedPipelinerSyncProcessed;
use App\Events\Pipeliner\QueuedPipelinerSyncProgress;
use App\Events\Pipeliner\QueuedPipelinerSyncRemoteEntitySkipped;
use App\Events\Pipeliner\QueuedPipelinerSyncStarting;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Exceptions\PipelinerIntegrationException;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Jobs\Pipeliner\QueuedPipelinerDataSync;
use App\Models\Opportunity;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelScrollCursor;
use App\Models\PipelinerModelUpdateLog;
use App\Models\User;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Models\QueueSyncResult;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use App\Services\Pipeliner\Strategies\StrategyNameResolver;
use App\Services\Pipeliner\Strategies\SyncStrategyCollection;
use Carbon\Carbon;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class PipelinerDataSyncService implements LoggerAware, CauserAware, FlagsAware, CorrelationAware
{
    const PULL = 1 << 0;
    const PUSH = 1 << 1;

    protected ?Model $causer;

    protected \Closure $strategyFilter;

    protected int $flags = self::PULL | self::PUSH;

    protected string $correlationId;

    #[Pure]
    public function __construct(protected ConnectionInterface     $connection,
                                protected SyncPipelinerDataStatus $dataSyncStatus,
                                protected SyncStrategyCollection  $syncStrategies,
                                protected BusDispatcher           $busDispatcher,
                                protected EventDispatcher         $eventDispatcher,
                                protected Cache                   $cache,
                                protected Config                  $config,
                                protected LoggerInterface         $logger = new NullLogger())
    {
        $this->correlationId = (string)Str::orderedUuid();
        $this->strategyFilter = static fn() => true;
    }

    protected function prepareStrategies(): void
    {
        $pipeline = $this->getDefaultPipeline();

        foreach ($this->syncStrategies as $strategy) {
            $strategy->setPipeline($pipeline);
        }
    }

    public function sync(): void
    {
        $this->dataSyncStatus->enable();

        $this->prepareStrategies();

        $this->logger->debug("Computing total count of pending entities...");

        $events = new Dispatcher();

        $counts = [];

        foreach ($this->syncStrategies as $key => $strategy) {
            if (false === $this->determineStrategyCanBeApplied($strategy)) {
                continue;
            }

            $counts[$key] = $strategy->countPending();

            if (0 === $counts[$key]) {
                $this->logger->debug(sprintf("Nothing to sync using: %s", class_basename($strategy::class)));
            }
        }

        $pendingCount = $totalCount = array_sum($counts);

        $events->listen('progress', function () use ($totalCount, &$pendingCount): void {
            $pendingCount--;

            $this->logger->debug("Progress, total pending count: $pendingCount");

            $this->eventDispatcher->dispatch(
                new QueuedPipelinerSyncProgress(
                    totalEntities: $totalCount,
                    pendingEntities: $pendingCount,
                    causer: $this->causer,
                    correlationId: $this->correlationId,
                )
            );
        });

        $events->listen('skipped', function (): void {

            $arg = func_get_arg(0);

            if ($arg instanceof Opportunity) {
                $this->eventDispatcher->dispatch(
                    new QueuedPipelinerSyncLocalEntitySkipped(
                        $arg,
                        $this->causer,
                        correlationId: $this->correlationId,
                    )
                );
            }

            if ($arg instanceof OpportunityEntity) {
                $this->eventDispatcher->dispatch(
                    new QueuedPipelinerSyncRemoteEntitySkipped(
                        $arg,
                        $this->causer,
                        correlationId: $this->correlationId,
                    )
                );
            }

        });

        $this->eventDispatcher->dispatch(
            new QueuedPipelinerSyncStarting(
                totalEntities: $totalCount,
                pendingEntities: $pendingCount,
                causer: $this->causer,
                correlationId: $this->correlationId,
            )
        );

        foreach ($counts as $class => $count) {
            if ($count > 0) {
                $this->logger->info(sprintf('Syncing entities using: %s', class_basename($class)));

                $this->syncUsing($this->syncStrategies[$class], $events);
            }

        }

        $this->dataSyncStatus->disable();

        if ($this->causer instanceof User) {
            $this->eventDispatcher->dispatch(new QueuedPipelinerSyncProcessed($this->causer, $totalCount, $pendingCount));
        }
    }

    #[ArrayShape(['applied' => "array[]"])]
    public function syncModel(Model $model): array
    {
        $this->prepareStrategies();

        $applicableStrategies = [];

        foreach ($this->syncStrategies as $strategy) {
            if ($strategy->isApplicableTo($model) && $this->determineStrategyCanBeApplied($strategy)) {
                $applicableStrategies[] = $strategy;
            }
        }

        $applicableStrategies = collect($applicableStrategies)
            ->sortByDesc(static function (SyncStrategy $strategy) use ($model): int|float {
                // When the model doesn't have the pipeliner reference yet,
                // the pull strategy must be applied at the end.
                if ($strategy instanceof PullStrategy && null === $model->pl_reference) {
                    return -INF;
                }

                $metadata = $strategy instanceof PullStrategy
                    ? $strategy->getMetadata($model->pl_reference)
                    : [
                        'id' => $model->getKey(),
                        'created' => Carbon::instance($model->{$model->getCreatedAtColumn()}),
                        'modified' => Carbon::instance($model->{$model->getUpdatedAtColumn()}),
                    ];

                return Carbon::instance($metadata['modified'])->roundSeconds(5)->getTimestamp();
            });

        $appliedStrategies = $applicableStrategies
            ->map(function (SyncStrategy $strategy) use ($model): array {
                $ok = false;
                $e = null;

                $this->logger->info(sprintf("Syncing model using: %s.", (string)StrategyNameResolver::from($strategy)), [
                    'model_id' => $model->getKey(),
                    'model_type' => class_basename($model::class),
                ]);

                try {
                    $this->syncModelUsing($model, $strategy);
                    $this->persistSyncStrategyLog($model, $strategy);

                    $ok = true;
                } catch (PipelinerIntegrationException|PipelinerSyncException $e) {
                    $this->logger->warning('Strategy has been skipped due to errors.', [
                        'error' => trim($e->getMessage()),
                    ]);

                    report($e);
                } catch (Throwable $e) {
                    report($e);
                }

                return [
                    'strategy' => (string)StrategyNameResolver::from($strategy),
                    'ok' => $ok,
                    'errors' => $this->prepareSyncException($e),
                ];
            });

        return $appliedStrategies
            ->values()
            ->pipe(function (Collection $collection) {
                return [
                    'applied' => $collection->all(),
                ];
            });
    }

    protected function prepareSyncException(?Throwable $e): array|null
    {
        if ($e instanceof PipelinerIntegrationException) {

            if ($e instanceof GraphQlRequestException) {
                return collect($e->errors)
                    ->map(static function (array $error) {
                        $array = [
                            'message' => $error['message'],
                            'api_error' => null,
                        ];

                        if (isset($error['api_error'])) {
                            $array['api_error'] = [
                                'code' => $error['api_error']['code'],
                                'name' => $error['api_error']['name'],
                                'message' => $error['api_error']['message'],
                            ];
                        }

                        return $array;
                    })
                    ->all();
            }

            return [
                'message' => $e->getMessage(),
                'api_error' => null,
            ];
        }

        return null;
    }

    protected function syncModelUsing(Model $model, SyncStrategy $strategy): void
    {
        if ($strategy instanceof PushStrategy) {
            $this->pushModelUsing($model, $strategy);

            return;
        }

        if ($strategy instanceof PullStrategy) {
            $this->pullModelUsing($model, $strategy);

            return;
        }

        throw new PipelinerSyncException(sprintf("Strategy must implement either %s or %s.", PushStrategy::class, PullStrategy::class));
    }

    protected function pushModelUsing(Model $model, PushStrategy $strategy): void
    {
        $strategy->sync($model);
    }

    protected function pullModelUsing(Model $model, PullStrategy $strategy): void
    {
        if (null !== $model->pl_reference) {
            $strategy->syncByReference($model->pl_reference);
        }
    }

    /**
     * @throws PipelinerSyncException
     */
    protected function syncUsing(SyncStrategy $strategy, EventDispatcher $events): void
    {
        if ($strategy instanceof PushStrategy) {
            $this->pushUsing($strategy, $events);

            return;
        }

        if ($strategy instanceof PullStrategy) {
            $this->pullUsing($strategy, $events);

            return;
        }

        throw new PipelinerSyncException(sprintf("Strategy must implement either %s or %s.", PushStrategy::class, PullStrategy::class));
    }

    protected function pullUsing(PullStrategy $strategy, EventDispatcher $events): void
    {
        $pullErrorOccurred = false;
        $anyCursorSaved = false;
        $latestCursor = null;

        foreach ($strategy->iteratePending() as $cursor => $entity) {
            $latestCursor ??= $cursor;

            $this->logger->info(sprintf('Fetching new %s.', class_basename($entity::class)), [
                'plReference' => $entity->id,
            ]);

            try {
                $model = $strategy->sync($entity);

                if (!$model->wasRecentlyCreated) {
                    $this->logger->info(sprintf('%s already exists.', class_basename($entity::class)), [
                        'plReference' => $entity->id,
                    ]);
                } else {
                    $this->logger->info(sprintf('%s fetched.', class_basename($entity::class)), [
                        'id' => $model->getKey(),
                        'plReference' => $entity->id,
                    ]);
                }

                $this->persistSyncStrategyLog($model, $strategy);
            } catch (PipelinerIntegrationException|PipelinerSyncException $e) {
                report($e);

                $pullErrorOccurred = true;

                $this->logger->warning('Remote %s skipped due to errors.', [
                    'plReference' => $entity->id,
                    'error' => trim($e->getMessage()),
                ]);

                $events->dispatch('skipped', $entity);
            }

            $events->dispatch('progress');

            if (false === $pullErrorOccurred && $cursor !== $latestCursor) {
                $this->logger->info('Page entities were handled. Persisting the new scroll cursor.', [
                    'cursor' => $cursor,
                ]);

                $this->persistScrollCursor($cursor, $strategy);

                $anyCursorSaved = true;
                $latestCursor = $cursor;
            }
        }

        if (null !== $latestCursor && false === $pullErrorOccurred && false === $anyCursorSaved) {
            $this->logger->info('All pages were handled. Persisting the latest scroll cursor.', [
                'cursor' => $latestCursor,
            ]);

            $this->persistScrollCursor($latestCursor, $strategy);
        }
    }

    private function persistSyncStrategyLog(Model $model, SyncStrategy $strategy): void
    {
        tap(new PipelinerSyncStrategyLog(), function (PipelinerSyncStrategyLog $log) use ($model, $strategy) {
            $log->model()->associate($model);
            $log->strategy_name = (string)StrategyNameResolver::from($strategy);

            $this->connection->transaction(static fn() => $log->save());
        });
    }

    private function persistScrollCursor(string $cursor, SyncStrategy $strategy): void
    {
        tap(new PipelinerModelScrollCursor, function (PipelinerModelScrollCursor $cursorModel) use ($strategy, $cursor) {
            $cursorModel->pipeline()->associate($strategy->getPipeline());
            $cursorModel->model_type = $strategy->getModelType();
            $cursorModel->cursor = $cursor;

            $this->connection->transaction(static fn() => $cursorModel->save());
        });
    }

    protected function pushUsing(PushStrategy $strategy, EventDispatcher $events): void
    {
        $lastSyncedModel = null;
        $updateErrorOccurred = false;

        foreach ($strategy->iteratePending() as $model) {
            /** @var Model $model */

            $this->logger->info(sprintf('Pushing the %s.', class_basename($model)), [
                'id' => $model->getKey(),
                'plReference' => $model->pl_reference,
                'updatedAt' => $model->{$model->getUpdatedAtColumn()},
            ]);

            try {
                $strategy->sync($model);

                $this->logger->info(sprintf('%s synced.', class_basename($model)), [
                    'id' => $model->getKey(),
                ]);

                $this->persistSyncStrategyLog($model, $strategy);
            } catch (PipelinerIntegrationException|PipelinerSyncException $e) {
                report($e);

                $updateErrorOccurred = true;

                $this->logger->warning(sprintf("Local %s skipped due to errors.", class_basename($model)), [
                    'id' => $model->getKey(),
                    'error' => trim($e->getMessage()),
                    'graphQlErrors' => $e instanceof GraphQlRequestException ? $e->errors : null,
                ]);

                $events->dispatch('skipped', $model);
            }

            $events->dispatch('progress');

            $lastSyncedModel = $model;
        }

        if (false === $updateErrorOccurred && null !== $lastSyncedModel) {
            $this->logger->info('Persisting update log.', [
                'updatedAt' => $lastSyncedModel->{$lastSyncedModel->getUpdatedAtColumn()},
            ]);

            $this->persistUpdateLog($lastSyncedModel->{$lastSyncedModel->getUpdatedAtColumn()}, $strategy);
        }
    }

    private function determineStrategyCanBeApplied(SyncStrategy $strategy): bool
    {
        if (false === ($this->strategyFilter)($strategy)) {
            return false;
        }

        if ($this->isSyncMethodAllowed('pull') && self::PULL === ($this->flags & self::PULL) && $strategy instanceof PullStrategy) {
            return true;
        }

        if ($this->isSyncMethodAllowed('push') && self::PUSH === ($this->flags & self::PUSH) && $strategy instanceof PushStrategy) {
            return true;
        }

        return false;
    }

    private function isSyncMethodAllowed(string $method): bool
    {
        $allowedMethods = $this->config->get('pipeliner.sync.allowed_methods', ['*']);

        return '*' === $allowedMethods[0] || in_array($method, $allowedMethods, true);
    }

    private function persistUpdateLog(\DateTimeInterface $dateTime, SyncStrategy $strategy): void
    {
        tap(new PipelinerModelUpdateLog, function (PipelinerModelUpdateLog $log) use ($strategy, $dateTime): void {
            $log->pipeline()->associate($strategy->getPipeline());
            $log->model_type = $strategy->getModelType();
            $log->latest_model_updated_at = $dateTime;

            $this->connection->transaction(static fn() => $log->save());
        });
    }

    public function queueSync(array $strategies = []): QueueSyncResult
    {
        $job = new QueuedPipelinerDataSync($this->causer, $strategies);

        if (!(new UniqueLock($this->cache))->acquire($job)) {
            $this->logger->info("Sync pipeliner data job is processing yet.");

            return new QueueSyncResult(queued: false);
        }

        $this->logger->info("Sync pipeliner data job has been dispatched.");

        $this->dataSyncStatus->enable();

        $this->busDispatcher->dispatch($job);

        return new QueueSyncResult(queued: true);
    }

    public function getDataSyncStatus(): SyncPipelinerDataStatus
    {
        return $this->dataSyncStatus;
    }

    protected function getDefaultPipeline(): Pipeline
    {
        if (!app()->environment('production')) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return Pipeline::query()
                ->where('is_system', true)
                ->where('pipeline_name', 'DEV')
                ->sole();
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Pipeline::query()->where('is_default', true)->sole();
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
            $this->dataSyncStatus->setCauser($causer);
        });
    }

    public function setFlags(int $flags): static
    {
        return tap($this, fn() => $this->flags = $flags);
    }

    public function setStrategyFilter(\Closure $callback): static
    {
        return tap($this, fn() => $this->strategyFilter = $callback);
    }

    public function setCorrelation(string $id): static
    {
        return tap($this, fn() => $this->correlationId = $id);
    }
}