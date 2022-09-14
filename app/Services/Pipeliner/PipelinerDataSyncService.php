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
use App\Jobs\Pipeliner\QueuedPipelinerDataSync;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelScrollCursor;
use App\Models\PipelinerModelUpdateLog;
use App\Models\SalesUnit;
use App\Models\User;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Models\QueueSyncResult;
use App\Services\Pipeliner\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class PipelinerDataSyncService implements LoggerAware, CauserAware, FlagsAware, CorrelationAware
{
    const PULL = 1 << 0;
    const PUSH = 1 << 1;

    protected ?User $causer = null;

    protected \Closure $strategyFilter;

    protected int $flags = self::PULL | self::PUSH;

    protected string $correlationId;

    public function __construct(
        protected ConnectionInterface $connection,
        protected SyncPipelinerDataStatus $dataSyncStatus,
        protected SyncStrategyCollection $syncStrategies,
        protected BusDispatcher $busDispatcher,
        protected EventDispatcher $eventDispatcher,
        protected Cache $cache,
        protected Config $config,
        protected LoggerInterface $logger = new NullLogger()
    ) {
        $this->correlationId = (string) Str::orderedUuid();
        $this->strategyFilter = static fn() => true;
    }

    protected function prepareStrategies(): void
    {
        $units = $this->getEnabledSalesUnits()
            ->when($this->causer instanceof User, function (Collection $collection) {
                return $collection->filter(function (SalesUnit $unit) {
                    return $this->causer->salesUnits->contains($unit);
                });
            })
            ->values();

        foreach ($this->syncStrategies as $strategy) {
            $strategy->setSalesUnits(...$units->all());
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

        $this->logger->debug("Total count of pending entities: $pendingCount.", [
            'counts' => collect($counts)
                ->mapWithKeys(static fn(int $count, string $class): array => [class_basename($class) => $count])
                ->all(),
        ]);

        $events->listen('progress', function () use (&$totalCount, &$pendingCount): void {
            $pendingCount--;

            // When the pending entities were added after synchronization start,
            // the total count will be incremented.
            if ($pendingCount < 0) {
                $totalCount += abs($pendingCount);
                $pendingCount = 0;
            }

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

        $events->listen('skipped', function (object $entity, ?Throwable $e = null): void {
            if ($entity instanceof Model) {
                $this->eventDispatcher->dispatch(
                    new QueuedPipelinerSyncLocalEntitySkipped($entity, $this->causer, $this->correlationId, $e)
                );
            } else {
                $this->eventDispatcher->dispatch(
                    new QueuedPipelinerSyncRemoteEntitySkipped($entity, $this->causer, $this->correlationId, $e)
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
            $this->eventDispatcher->dispatch(new QueuedPipelinerSyncProcessed($this->causer, $totalCount,
                $pendingCount));
        }
    }

    #[ArrayShape(['applied' => "array[]"])]
    public function syncModel(Model $model): array
    {
        $this->logger->info('Starting model syncing.', [
            'model_id' => $model->getKey(),
            'model_type' => class_basename($model),
        ]);

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

                $this->logger->info(sprintf("Syncing model using: %s.", (string) StrategyNameResolver::from($strategy)),
                    [
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
                    'strategy' => (string) StrategyNameResolver::from($strategy),
                    'ok' => $ok,
                    'errors' => $this->prepareSyncException($e),
                ];
            });

        $this->logger->info('Model syncing completed.', [
            'model_id' => $model->getKey(),
            'model_type' => class_basename($model),
            'applied' => $appliedStrategies->values()->all(),
        ]);

        return $appliedStrategies
            ->values()
            ->pipe(function (BaseCollection $collection) {
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

        throw new PipelinerSyncException(sprintf("Strategy must implement either %s or %s.", PushStrategy::class,
            PullStrategy::class));
    }

    protected function pushModelUsing(Model $model, PushStrategy $strategy): void
    {
        $strategy->sync($model);

        if ($strategy instanceof ImpliesSyncOfHigherHierarchyEntities) {
            /** @var $strategy PushStrategy&ImpliesSyncOfHigherHierarchyEntities */
            $this->syncHigherHierarchyEntities($strategy, $model);
        }
    }

    protected function pullModelUsing(Model $model, PullStrategy $strategy): void
    {
        if (null !== $model->pl_reference) {
            $strategy->syncByReference($model->pl_reference);
            $model->refresh();

            if ($strategy instanceof ImpliesSyncOfHigherHierarchyEntities) {
                /** @var $strategy PullStrategy&ImpliesSyncOfHigherHierarchyEntities */
                $this->syncHigherHierarchyEntities($strategy, $strategy->getByReference($model->pl_reference));
            }
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

        throw new PipelinerSyncException(sprintf("Strategy must implement either %s or %s.", PushStrategy::class,
            PullStrategy::class));
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

                if ($strategy instanceof ImpliesSyncOfHigherHierarchyEntities) {
                    /** @var $strategy PushStrategy&ImpliesSyncOfHigherHierarchyEntities */
                    $this->syncHigherHierarchyEntities($strategy, $entity);
                }

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
            $log->strategy_name = (string) StrategyNameResolver::from($strategy);

            $this->connection->transaction(static fn() => $log->save());
        });
    }

    private function persistScrollCursor(string $cursor, SyncStrategy $strategy): void
    {
        if ($this->causer instanceof User) {
            $this->logger->debug("The scroll cursor will not be saved because the sync is run by user.");

            return;
        }

        tap(new PipelinerModelScrollCursor, function (PipelinerModelScrollCursor $cursorModel) use (
            $strategy,
            $cursor
        ) {
            $cursorModel->model_type = $strategy->getModelType();
            $cursorModel->cursor = $cursor;

            $this->connection->transaction(static fn() => $cursorModel->save());
        });
    }

    private function persistUpdateLog(\DateTimeInterface $dateTime, SyncStrategy $strategy): void
    {
        if ($this->causer instanceof User) {
            $this->logger->debug("The model update log will not be saved because the sync is run by user.");

            return;
        }

        tap(new PipelinerModelUpdateLog, function (PipelinerModelUpdateLog $log) use ($strategy, $dateTime): void {
            $log->model_type = $strategy->getModelType();
            $log->latest_model_updated_at = $dateTime;

            $this->connection->transaction(static fn() => $log->save());
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

                if ($strategy instanceof ImpliesSyncOfHigherHierarchyEntities) {
                    /** @var $strategy PushStrategy&ImpliesSyncOfHigherHierarchyEntities */
                    $this->syncHigherHierarchyEntities($strategy, $model);
                }

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

                $events->dispatch('skipped', [$model, $e]);
            }

            $events->dispatch('progress');

            $lastSyncedModel = $model;
        }

        if (false === $updateErrorOccurred && null !== $lastSyncedModel) {
            if ($lastSyncedModel->usesTimestamps() === false) {
                $this->logger->warning("Model doesn't support timestamps. Update log won't be persisted.");
            } else {
                $this->logger->info('Persisting update log.', [
                    'updatedAt' => $lastSyncedModel->{$lastSyncedModel->getUpdatedAtColumn()},
                ]);

                $this->persistUpdateLog($lastSyncedModel->{$lastSyncedModel->getUpdatedAtColumn()}, $strategy);
            }
        }
    }

    private function resolveSuitableStrategiesFor(mixed $entity, string|object $methodInterface): SyncStrategyCollection
    {
        $methodInterface = match (true) {
            is_a($methodInterface, PushStrategy::class, true) => PushStrategy::class,
            is_a($methodInterface, PullStrategy::class, true) => PullStrategy::class,
            default => throw new \InvalidArgumentException(
                sprintf("MethodInterface must be an instance of either [%s], or [%s]", PushStrategy::class,
                    PullStrategy::class)
            )
        };

        return LazyCollection::make(function () use ($entity): \Generator {
            return yield from $this->syncStrategies;
        })
            ->whereInstanceOf($methodInterface)
            ->filter(static function (SyncStrategy $strategy) use ($entity): bool {
                return $strategy->isApplicableTo($entity);
            })
            ->pipe(function (LazyCollection $collection) {
                return new SyncStrategyCollection(...$collection->all());
            });
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

    protected function getEnabledSalesUnits(): Collection
    {
        /** @var Collection $units */
        $units = SalesUnit::query()
            ->where('is_enabled', true)
            ->get();

        $allowedUnitNames = $this->config->get('pipeliner.sync.allowed_sales_units', []);

        return $units->each(static function (SalesUnit $unit) use ($allowedUnitNames) {
            throw_unless(
                head($allowedUnitNames) === '*' || in_array($unit->unit_name, $allowedUnitNames, true),
                PipelinerSyncException::nonAllowedSalesUnit($unit)
            );
        })
            ->pipe(static function (Collection $collection): Collection {
                throw_if($collection->isEmpty(), PipelinerSyncException::noSalesUnitIsEnabled());

                return $collection;
            });
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

    protected function syncHigherHierarchyEntities(
        SyncStrategy&ImpliesSyncOfHigherHierarchyEntities $strategy,
        object $relatedEntity
    ): void {
        foreach ($strategy->resolveHigherHierarchyEntities($relatedEntity) as $entity) {
            $hhStrategies = $this->resolveSuitableStrategiesFor($entity, $strategy);

            $ctx = $entity instanceof Model
                ? [
                    'id' => $entity->getKey(),
                    'plReference' => $entity->pl_reference,
                    'updatedAt' => $entity->{$entity->getUpdatedAtColumn()},
                    'strategies' => collect($hhStrategies)->keys()->map(class_basename(...))->all(),
                ]
                : [
                    'plReference' => $entity->id,
                ];

            $this->logger->debug(
                sprintf(
                    "%s higher hierarchy %s.",
                    $strategy instanceof PushStrategy ? 'Pushing' : 'Fetching',
                    class_basename($entity)
                ),
                $ctx);

            foreach ($hhStrategies as $sStrategy) {
                $sStrategy->sync($entity);
            }
        }
    }
}