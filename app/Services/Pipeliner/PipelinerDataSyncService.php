<?php

namespace App\Services\Pipeliner;

use App\Contracts\CauserAware;
use App\Contracts\CorrelationAware;
use App\Contracts\FlagsAware;
use App\Contracts\LoggerAware;
use App\Events\Pipeliner\QueuedPipelinerSyncFailed;
use App\Events\Pipeliner\QueuedPipelinerSyncProcessed;
use App\Events\Pipeliner\QueuedPipelinerSyncStarting;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Exceptions\PipelinerIntegrationException;
use App\Jobs\Pipeliner\QueuedPipelinerDataSync;
use App\Jobs\Pipeliner\SyncPipelinerEntity;
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
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
        protected QueueingDispatcher $busDispatcher,
        protected EventDispatcher $eventDispatcher,
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

        $this->prepareStrategies();

        $this->logger->debug("Computing total count of pending entities...");

        $pendingEntities = LazyCollection::make(function (): \Generator {
            yield from $this->syncStrategies;
        })
            ->filter(function (SyncStrategy $strategy): bool {
                return $this->determineStrategyCanBeApplied($strategy);
            })
            ->sortBy(function (SyncStrategy $strategy): int|float {
                $name = StrategyNameResolver::from($strategy)->__toString();

                $index = array_search($name, $this->config->get('pipeliner.sync.aggregate_strategies'), true);

                if (false === $index) {
                    return INF;
                }

                return $index;
            })
            ->map(function (SyncStrategy $strategy): LazyCollection {
                return LazyCollection::make(static function () use ($strategy): \Generator {
                    yield from $strategy->iteratePending();
                })
                    ->values();
            })
            ->each(function (LazyCollection $pending, string $class): void {
                if ($pending->isEmpty()) {
                    $this->logger->debug(sprintf("Nothing to sync using: %s", class_basename($class)));
                }
            });

        $counts = $pendingEntities->map(static function (LazyCollection $collection): int {
            return $collection->eager()->count();
        });

        $pendingCount = $counts->sum();

        $this->dataSyncStatus->setTotal($counts->sum());

        $this->logger->debug("Total count of pending entities: $pendingCount.", [
            'counts' => $counts
                ->mapWithKeys(static fn(int $count, string $class): array => [class_basename($class) => $count])
                ->all(),
        ]);

        $this->eventDispatcher->dispatch(
            new QueuedPipelinerSyncStarting(total: $pendingCount, pending: $pendingCount)
        );

        foreach ($pendingEntities as $class => $collection) {
            if (!$this->dataSyncStatus->running()) {
                break;
            }

            if ($collection->isNotEmpty()) {
                $this->logger->info(sprintf('Syncing entities using: %s', class_basename($class)));

                $this->syncUsing($this->syncStrategies[$class], $collection);
            }
        }

        $this->eventDispatcher->dispatch(new QueuedPipelinerSyncProcessed($pendingCount));
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

    protected function syncUsing(SyncStrategy $strategy, \Traversable $pending): void
    {
        $this->logger->info("Loading pending entities...", [
            'strategy' => class_basename($strategy),
        ]);

        /** @var Batch $batch */
        $batch = LazyCollection::make(static function () use ($pending): \Generator {
            yield from $pending;
        })
            ->map(function (array $item) use ($strategy): SyncPipelinerEntity {
                return new SyncPipelinerEntity($strategy, $item['id'], $this->causer);
            })
            ->values()
            ->pipe(function (LazyCollection $pending): Batch {
                $statusOwner = $this->dataSyncStatus->getOwner();

                return $this->busDispatcher
                    ->batch($pending->all())
                    ->onQueue('pipeliner-sync')
                    ->catch(static function (Batch $batch, Throwable $e) use ($statusOwner): void {
                        app('log')->channel('pipeliner')->error($e);

                        app(SyncPipelinerDataStatus::class)
                            ->setOwner($statusOwner)
                            ->release();

                        event(new QueuedPipelinerSyncFailed($e));
                    })
                    ->dispatch();
            });

        $this->logger->info("Pending entities are loaded into batch. Waiting...", [
            'strategy' => class_basename($strategy),
            'batch' => $batch->toArray(),
        ]);

        while (true) {
            $batch = $batch->fresh();

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

            usleep(1000 * 1000);
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