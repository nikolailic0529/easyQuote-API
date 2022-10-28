<?php

namespace App\Services\Pipeliner;

use App\Contracts\CauserAware;
use App\Contracts\CorrelationAware;
use App\Contracts\FlagsAware;
use App\Contracts\LoggerAware;
use App\Events\Pipeliner\AggregateSyncCompleted;
use App\Events\Pipeliner\AggregateSyncFailed;
use App\Events\Pipeliner\AggregateSyncStarting;
use App\Events\Pipeliner\ModelSyncCompleted;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Exceptions\PipelinerIntegrationException;
use App\Jobs\Pipeliner\QueuedPipelinerDataSync;
use App\Jobs\Pipeliner\SyncPipelinerEntity;
use App\Models\Opportunity;
use App\Models\SalesUnit;
use App\Models\User;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Models\QueueCounts;
use App\Services\Pipeliner\Models\QueueSyncResult;
use App\Services\Pipeliner\RecordCorrelation\RecordCorrelationService;
use App\Services\Pipeliner\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use App\Services\Pipeliner\Strategies\PushCompanyStrategy;
use App\Services\Pipeliner\Strategies\PushOpportunityStrategy;
use App\Services\Pipeliner\Strategies\StrategyNameResolver;
use App\Services\Pipeliner\Strategies\SyncStrategyCollection;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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
        protected PipelinerSyncAggregate $syncAggregate,
        protected SyncStrategyCollection $syncStrategies,
        protected RecordCorrelationService $recordCorrelationService,
        protected QueueingDispatcher $busDispatcher,
        protected EventDispatcher $eventDispatcher,
        protected LockProvider $lockProvider,
        protected Cache $cache,
        protected Config $config,
        protected Guard $guard,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
        $this->correlationId = (string) Str::orderedUuid();
        $this->strategyFilter = static fn() => true;
    }

    protected function prepareStrategies(): void
    {
        $units = $this->getAllowedSalesUnits();

        foreach ($this->syncStrategies as $strategy) {
            $strategy->setSalesUnits(...$units->all());
        }
    }

    public function sync(): void
    {
        if ($this->causer instanceof Authenticatable) {
            $this->guard->setUser($this->causer);
        }

        if (!$this->syncAggregate->hasId()) {
            $this->syncAggregate->withId(Str::orderedUuid()->toString());
        }

        $this->prepareStrategies();

        $this->logger->debug("Computing total count of pending entities...");

        $pendingEntities = LazyCollection::make(function (): \Generator {
            yield from $this->syncStrategies;
        })
            ->filter(function (SyncStrategy $strategy): bool {
                return $this->determineStrategyCanBeApplied($strategy);
            })
            ->sortBy(function (SyncStrategy $strategy): int|float {
                return $this->valueForStrategySorting($strategy);
            })
            ->map(function (SyncStrategy $strategy): LazyCollection {
                return LazyCollection::make(static function () use ($strategy): \Generator {
                    yield from $strategy->iteratePending();
                })
                    ->values();
            });

        $pendingChains = $this->chainPending($pendingEntities);

        $pendingCount = $pendingChains->count();
        $pendingCountByStrategy = $pendingChains->collapse()
            ->groupBy('strategy')
            ->mapWithKeys(static function (iterable $items, string $strategy): array {
                return [(string)StrategyNameResolver::from($strategy) => collect($items)->count()];
            })
            ->all();

        $this->dataSyncStatus->setTotal($pendingCount);

        $this->logger->debug("Total count of pending entities: $pendingCount.", [
            'pending_counts_by_strategy' => $pendingCountByStrategy,
        ]);

        $this->eventDispatcher->dispatch(
            new AggregateSyncStarting(
                aggregateId: $this->syncAggregate->id,
                total: $pendingCount,
                pending: $pendingCount,
            )
        );

        $success = true;

        if ($pendingChains->isNotEmpty()) {
            $success = $this->awaitSync($pendingChains);
        }

        if ($success) {
            $this->eventDispatcher->dispatch(new AggregateSyncCompleted(
                aggregateId: $this->syncAggregate->id,
                total: $pendingCount,
            ));
        }

        $this->dataSyncStatus->release();
    }

    /**
     * @param  iterable<class-string, iterable>  $pending
     * @return BaseCollection
     */
    protected function chainPending(iterable $pending): BaseCollection
    {
        /** @var BaseCollection{string, BaseCollection} $strategyItemsMap */
        $strategyItemsMap = LazyCollection::make(static function () use ($pending): \Generator {
            yield from $pending;
        })
            ->map(function (iterable $pending, string $strategy): BaseCollection {
                return LazyCollection::wrap($pending)->collect();
            })
            ->collect();

        $strategiesToBeChained = $strategyItemsMap->keys();

        $chains = collect();

        do {
            $currentStrategy = $strategiesToBeChained->shift();

            $currentStrategyChains = $strategyItemsMap[$currentStrategy]
                ->lazy()
                ->map(function (array $item) use ($currentStrategy) {
                    return collect([
                        [
                            'strategy' => $currentStrategy,
                            'item' => $item,
                        ],
                    ]);
                });

            if ($strategiesToBeChained->isNotEmpty()) {
                $currentStrategyChains = $currentStrategyChains
                    ->map(function (BaseCollection $chain) use ($strategyItemsMap, $strategiesToBeChained) {
                        $item = $chain->first()['item'];

                        $reference = $item['pl_reference'] ?? null;

                        if (null === $reference) {
                            return $chain;
                        }

                        $toBeChainedWithCurrent = $strategiesToBeChained
                            ->map(function (string $strategy) use ($item, $reference, $strategyItemsMap) {
                                /** @var BaseCollection $itemsOfStrategy */
                                $itemsOfStrategy = $strategyItemsMap[$strategy];

                                return $itemsOfStrategy
                                    ->lazy()
                                    ->filter(function (array $another) use ($strategy, $item): bool {
                                        return $this->recordCorrelationService->matches($strategy, $item, $another);
                                    })
                                    ->keys()
                                    ->map(static function (int $key) use ($strategy, $itemsOfStrategy) {
                                        $item = $itemsOfStrategy->pull($key);

                                        return [
                                            'strategy' => $strategy,
                                            'item' => $item
                                        ];
                                    })
                                    ->all();
                            })
                            ->values()
                            ->collapse();

                        if ($toBeChainedWithCurrent->isNotEmpty()) {
                            $chain->push(...$toBeChainedWithCurrent);
                        }

                        return $chain;
                });
            }

            $currentStrategyChains = $currentStrategyChains->collect();

            $chains = $chains->merge($currentStrategyChains);
        } while ($strategiesToBeChained->isNotEmpty());

        return $chains;
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
            ->sortBy(function (SyncStrategy $strategy): int|float {
                return $this->valueForStrategySorting($strategy);
            })
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

    /**
     * @throws PipelinerSyncException
     * @throws Throwable
     */
    public function queueModelSync(Model $model): array
    {
        if (method_exists($this->logger, 'withContext')) {
            $this->logger->withContext([
                'model_id' => $model->getKey(),
                'model_type' => class_basename($model),
                'causer_id' => $this->causer?->getKey(),
                'causer_email' => $this->causer?->email,
            ]);
        }

        $this->logger->info('Model sync: queueing...');

        $this->syncAggregate->withId(Str::orderedUuid()->toString());

        $this->prepareStrategies();

        $applicableStrategies = [];

        foreach ($this->syncStrategies as $strategy) {
            if ($strategy->isApplicableTo($model) && $this->determineStrategyCanBeApplied($strategy)) {
                $applicableStrategies[] = $strategy;
            }
        }

        $applicableStrategies = collect($applicableStrategies)
            ->sortBy(function (SyncStrategy $strategy): int|float {
                return $this->valueForStrategySorting($strategy);
            })
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

                return Carbon::instance($metadata['modified'])->roundSeconds(1)->getTimestamp();
            });

        /** @var BaseCollection $chain */
        $chain = $applicableStrategies
            ->lazy()
            ->filter(static function (SyncStrategy $strategy) use ($model): bool {
                if ($strategy instanceof PullStrategy && null === $model->pl_reference) {
                    return false;
                }

                return true;
            })
            ->map(function (SyncStrategy $strategy) use ($model): SyncPipelinerEntity {
                $entityReference = $strategy instanceof PullStrategy
                    ? $model->pl_reference
                    : $model->getKey();

                return new SyncPipelinerEntity(
                    strategy: $strategy,
                    entityReference: $entityReference,
                    aggregateId: $this->syncAggregate->id,
                    causer: $this->causer,
                );
            })
            ->values()
            ->pipe(static function (LazyCollection $collection): BaseCollection {
                return collect($collection->all());
            });

        $queuedStrategies = $chain
            ->map(static function (SyncPipelinerEntity $entity): array {
                return [
                    'strategy' => (string) StrategyNameResolver::from($entity->strategyClass),
                ];
            });

        $pendingBatch = $this->busDispatcher->batch([
            $chain->all(),
        ]);

        $lockKey = 'queue-model-sync:'.PipelinerDataSyncService::class.$model->getKey();

        if (!$this->lockProvider->lock($lockKey, 60 * 60)->get()) {
            throw PipelinerSyncException::modelAlreadyInSyncQueue($model);
        }

        $batch = $pendingBatch
            ->onQueue('pipeliner-sync')
            ->withOption('__model', $model->withoutRelations())
            ->withOption('__causer', $this->causer?->withoutRelations())
            ->withOption('__lock_key', $lockKey)
            ->finally(static function (Batch $batch): void {
                /** @var $model Model */
                /** @var $causer User|null */
                [$model, $causer] = [$batch->options['__model'], $batch->options['__causer']];

                app(LockProvider::class)
                    ->lock($batch->options['__lock_key'])
                    ->forceRelease();

                logger()->channel('pipeliner')->info('Model sync: completed.', [
                    'model_id' => $model->getKey(),
                    'model_type' => class_basename($model),
                    'causer_id' => $causer?->getKey(),
                    'causer_email' => $causer?->email,
                    'batch_id' => $batch->id,
                ]);

                event(new ModelSyncCompleted(
                    model: $model,
                    causer: $causer,
                ));
            })
            ->dispatch();

        $this->logger->info('Model sync: queued.', [
            'batch_id' => $batch->id,
            'queued_strategies' => $queuedStrategies->all(),
        ]);

        return [
            'batch' => Arr::except($batch->toArray(), 'options'),
            'queued' => $queuedStrategies->all(),
        ];
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

    protected function awaitSync(iterable $pending): bool
    {
        $this->logger->info("Loading pending entities...");

        $statusOwner = $this->dataSyncStatus->getOwner();
        $aggregateId = $this->syncAggregate->id;

        $batch = $this->busDispatcher
            ->batch([])
            ->onQueue('pipeliner-aggregate-sync')
            ->catch(static function (Batch $batch, Throwable $e) use ($statusOwner, $aggregateId): void {
                app('log')->channel('pipeliner')->error($e);

                app(SyncPipelinerDataStatus::class)
                    ->setOwner($statusOwner)
                    ->release();

                event(new AggregateSyncFailed($aggregateId, $e));
            })
            ->dispatch();

        LazyCollection::make(static function () use ($pending): \Generator {
            yield from $pending;
        })
            ->map(function (iterable $chain): array {
                $jobs = [];

                foreach ($chain as ['item' => $item, 'strategy' => $strategy]) {
                    $jobs[] = (new SyncPipelinerEntity(
                        strategy: $this->syncStrategies[$strategy],
                        entityReference: $item['id'],
                        aggregateId: $this->syncAggregate->id,
                        causer: $this->causer,
                        withoutOverlapping: $item['without_overlapping'] ?? [],
                        withProgress: true,
                    ));
                }

                return $jobs;
            })
            ->chunk(10)
            ->each(function (LazyCollection $chunk) use ($batch): void {
                $batch->add($chunk->all());
            });

        $batch = $batch->fresh();

        $this->logger->info("Pending entities are loaded into batch. Waiting...", [
            'batch' => $batch->toArray(),
        ]);

        while (true) {
            $batch = $batch->fresh();

            if ($batch->hasFailures()) {
                $this->logger->warning("Batch: failed.", [
                    'batch' => $batch->toArray(),
                ]);

                return false;
            }

            if ($batch->finished()) {
                $this->logger->info("Batch: finished.", [
                    'batch' => $batch->toArray(),
                ]);

                break;
            }

            if (!$this->dataSyncStatus->running()) {
                $batch->cancel();

                $this->logger->warning("Batch: cancelled.", [
                    'batch' => $batch->fresh()->toArray(),
                ]);

                break;
            }

            usleep(250 * 1000);
        }

        return true;
    }

    private function valueForStrategySorting(SyncStrategy $strategy): int|float
    {
        $name = StrategyNameResolver::from($strategy)->__toString();

        $index = array_search($name, $this->config->get('pipeliner.sync.aggregate_strategies'), true);

        if (false === $index) {
            return INF;
        }

        return $index;
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
            yield from $this->syncStrategies;
        })
            ->filter(static function (SyncStrategy $strategy) use ($entity, $methodInterface): bool {
                return $strategy instanceof $methodInterface && $strategy->isApplicableTo($entity);
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

    /**
     * @throws PipelinerSyncException
     */
    public function queueSync(array $strategies = []): QueueSyncResult
    {
        if ($this->getAllowedSalesUnits()->isEmpty()) {
            throw new PipelinerSyncException("Sync could not be started: none of the enabled units are associated with your user.");
        }

        $this->dataSyncStatus->setOwner($owner = Str::random());

        $job = new QueuedPipelinerDataSync(
            Str::orderedUuid()->toString(),
            $this->causer,
            $strategies,
            $owner,
        );

        if ($this->dataSyncStatus->acquire() === false) {
            $this->logger->info("Pipeliner sync status: locked.");

            return new QueueSyncResult(queued: false);
        }

        $this->logger->info("Pipeliner sync status: acquired.");

        $this->busDispatcher->dispatch($job);

        return new QueueSyncResult(queued: true);
    }

    public function getDataSyncStatus(): SyncPipelinerDataStatus
    {
        return $this->dataSyncStatus;
    }

    public function getQueueCounts(): QueueCounts
    {
        $counts = [
            'opportunities' => 0,
            'companies' => 0,
        ];

        $this->prepareStrategies();

        if ($this->isSyncMethodAllowed('push')) {
            $counts['opportunities'] = $this->syncStrategies[PushOpportunityStrategy::class]->countPending();
            $counts['companies'] = $this->syncStrategies[PushCompanyStrategy::class]->countPending();
        }

        return new QueueCounts(...$counts);
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

    protected function getAllowedSalesUnits(): Collection
    {
        return $this->getEnabledSalesUnits()
            ->when($this->causer instanceof User, function (Collection $collection): Collection {
                if ($this->causer->can('syncAny', SalesUnit::class)) {
                    return $collection;
                }

                return $collection->filter(function (SalesUnit $unit): bool {
                    return $this->causer->salesUnits->contains($unit);
                });
            })
            ->values();
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
                    'strategies' => collect($hhStrategies)->keys()->map(class_basename(...))->all(),
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