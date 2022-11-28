<?php

namespace App\Jobs\Pipeliner;

use App\Events\Pipeliner\AggregateSyncEntityProcessed;
use App\Events\Pipeliner\AggregateSyncEntitySkipped;
use App\Events\Pipeliner\AggregateSyncProgress;
use App\Events\Pipeliner\SyncStrategyPerformed;
use App\Integrations\Pipeliner\Exceptions\PipelinerIntegrationException;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\PipelinerSyncAggregate;
use App\Services\Pipeliner\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use App\Services\Pipeliner\Strategies\SyncStrategyCollection;
use App\Services\Pipeliner\SyncPipelinerDataStatus;
use App\Services\User\ApplicationUserResolver;
use Illuminate\Auth\AuthManager;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\LazyCollection;
use Psr\Log\LoggerInterface;

class SyncPipelinerEntity implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var class-string<SyncStrategy> */
    public readonly string $strategyClass;
    public readonly array $units;

    private ?SyncStrategyCollection $strategies = null;
    private ?Cache $cache = null;
    private ?LockProvider $lockProvider = null;
    private ?LoggerInterface $logger = null;
    private ?EventDispatcher $eventDispatcher = null;

    public function __construct(
        SyncStrategy $strategy,
        public readonly string $entityReference,
        public readonly string $aggregateId,
        public readonly string $chainId,
        public readonly ?Model $causer = null,
        public readonly array $withoutOverlapping = [],
        public readonly bool $withProgress = false,
    ) {
        $this->strategyClass = $strategy::class;
        $this->units = $strategy->getSalesUnits();
    }

    /**
     * Execute the job.
     *
     * @param  SyncStrategyCollection  $strategies
     * @param  SyncPipelinerDataStatus  $status
     * @param  PipelinerSyncAggregate  $aggregate
     * @param  LoggerInterface  $logger
     * @param  EventDispatcher  $eventDispatcher
     * @param  Cache  $cache
     * @param  LockProvider  $lockProvider
     * @return void
     */
    public function handle(
        SyncStrategyCollection $strategies,
        PipelinerSyncAggregate $aggregate,
        SyncPipelinerDataStatus $status,
        LoggerInterface $logger,
        EventDispatcher $eventDispatcher,
        Cache $cache,
        LockProvider $lockProvider,
        AuthManager $authManager,
        ApplicationUserResolver $defaultUserResolver,
    ): void {
        if ($this->batch()->canceled()) {
            return;
        }

        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->cache = $cache;
        $this->lockProvider = $lockProvider;
        $this->strategies = $strategies;

        $aggregate->withId($this->aggregateId);

        $this->setCauserToGuard($authManager, $defaultUserResolver);

        foreach ($strategies as $strategy) {
            $strategy->setSalesUnits(...$this->units);
        }

        $strategy = $strategies[$this->strategyClass];

        if ($this->cache->get($this->chainReleaseKey())) {
            $this->logger->warning('Syncing: skipped because chain was released.', [
                'id' => $this->entityReference,
                'strategy' => class_basename($this->strategyClass),
            ]);

            $this->advanceProgressIfNeeded($status);

            return;
        }

        $entity = $strategy->getByReference($this->entityReference);

        $logger->info('Syncing: starting', [
            'id' => $entity->id,
            'strategy' => class_basename($this->strategyClass),
            'chained_count' => count($this->chained),
        ]);

        try {
            $eventDispatcher->listen(SyncStrategyPerformed::class, $this->incrementOverlappingCounterOnEvent(...));

            $arguments = $this->strategyHasOptions()
                ? [$entity, 'batchId' => $this->batchId]
                : [$entity];

            $result = $strategy->sync(...$arguments);

            if ($strategy instanceof ImpliesSyncOfHigherHierarchyEntities) {
                /** @var SyncStrategy&ImpliesSyncOfHigherHierarchyEntities $strategy */
                $this->syncHigherHierarchyEntities($strategy, $entity);
            }

            $logger->info('Syncing: success', [
                'id' => $entity->id,
                'strategy' => class_basename($this->strategyClass),
            ]);

            $eventDispatcher->dispatch(
                new AggregateSyncEntityProcessed(
                    aggregateId: $this->aggregateId,
                    entity: $entity,
                    strategy: $strategy::class,
                    causer: $this->causer,
                )
            );
        } catch (PipelinerIntegrationException|PipelinerSyncException $e) {
            report($e);

            $logger->warning('Syncing: skipped', [
                'id' => $entity->id,
                'error' => trim($e->getMessage()),
                'strategy' => class_basename($this->strategyClass),
                'chained_count' => count($this->chained),
            ]);

            $eventDispatcher->dispatch(
                new AggregateSyncEntitySkipped(
                    aggregateId: $this->aggregateId,
                    entity: $entity,
                    strategy: $strategy::class,
                    causer: $this->causer,
                    e: $e,
                )
            );

            $cache->add($this->chainReleaseKey(), true);

            $logger->warning('Syncing: chain released.', [
                'chained' => count($this->chained),
            ]);
        } finally {
            $this->releaseLocks();
        }

        $this->advanceProgressIfNeeded($status);
    }

    protected function advanceProgressIfNeeded(SyncPipelinerDataStatus $status): void
    {
        if ($this->withProgress && count($this->chained) === 0) {
            $this->logger->info('Syncing: chain completed');

            $status->incrementProcessed();

            if ($this->batch()->cancelled()) {
                return;
            }

            $this->lockProvider->lock(AggregateSyncProgress::class, 5)
                ->get(function () use ($status): void {
                    $this->eventDispatcher->dispatch(
                        new AggregateSyncProgress(
                            total: $status->total(),
                            pending: $status->pending(),
                        )
                    );
                });
        }
    }

    public function uniqueId(): string
    {
        return 'pipeliner-sync:'.static::class.$this->batchId.$this->entityReference.$this->strategyClass;
    }

    public function chainReleaseKey(): string
    {
        return 'pipeliner-sync-chain-release:'.static::class.$this->chainId;
    }

    private function strategyHasOptions(): bool
    {
        $class = new \ReflectionClass($this->strategyClass);

        $method = $class->getMethod('sync');

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() === 'options') {
                return true;
            }
        }

        return false;
    }

    protected function syncHigherHierarchyEntities(
        SyncStrategy&ImpliesSyncOfHigherHierarchyEntities $strategy,
        object $relatedEntity,
    ): void {
        foreach ($strategy->resolveHigherHierarchyEntities($relatedEntity) as $entity) {
            $hhStrategies = $this->resolveSuitableStrategiesFor($entity, $strategy);

            foreach ($hhStrategies as $sStrategy) {
                $lockName = 'pipeliner-sync:'.static::class.$this->batchId.$this->entityReference.$this->strategyClass;

                $acquired = $this->lockProvider->lock($lockName, 60 * 10)->get();

                if (!$acquired) {
                    continue;
                }

                try {
                    $sStrategy->sync($entity);
                } catch (PipelinerIntegrationException|PipelinerSyncException $e) {
                    report($e);

                    $this->logger->warning('Syncing [higher-hierarchy]: skipped', [
                        'id' => $entity->id,
                        'error' => trim($e->getMessage()),
                        'strategy' => class_basename($sStrategy),
                    ]);

                    $this->eventDispatcher->dispatch(
                        new AggregateSyncEntitySkipped(
                            aggregateId: $this->aggregateId,
                            entity: $entity,
                            strategy: $sStrategy::class,
                            causer: $this->causer,
                            e: $e,
                        )
                    );
                }
            }
        }
    }

    private function resolveSuitableStrategiesFor(
        mixed $entity,
        string|object $methodInterface
    ): SyncStrategyCollection {
        $methodInterface = match (true) {
            is_a($methodInterface, PushStrategy::class, true) => PushStrategy::class,
            is_a($methodInterface, PullStrategy::class, true) => PullStrategy::class,
            default => throw new \InvalidArgumentException(
                sprintf("MethodInterface must be an instance of either [%s], or [%s]", PushStrategy::class,
                    PullStrategy::class)
            )
        };

        return LazyCollection::make(function () use ($entity): \Generator {
            return yield from $this->strategies;
        })
            ->whereInstanceOf($methodInterface)
            ->filter(static function (SyncStrategy $strategy) use ($entity): bool {
                return $strategy->isApplicableTo($entity);
            })
            ->pipe(function (LazyCollection $collection) {
                return new SyncStrategyCollection(...$collection->all());
            });
    }

    public function incrementOverlappingCounterOnEvent(SyncStrategyPerformed $event): void
    {
        $this->cache->increment(
            $this->getOverlappingCounterKey($event->model->getKey())
        );
    }

    private function getOverlappingCounterKey(string $key): string
    {
        return static::class.':overlapping-counter'.$this->batchId.$key.$this->strategyClass;
    }

    private function getOverlappingLock(string $key): Lock
    {
        $name = static::class.':overlapping-lock'.$this->batchId.$key.$this->strategyClass;

        return $this->lockProvider->lock($name, seconds: 180, owner: $this->job->uuid());
    }

    private function overlaps(): bool
    {
        $keys = collect($this->withoutOverlapping);

        if ($keys->isEmpty()) {
            return false;
        }

        return $keys
            ->map(function (string $key): bool {
                $count = (int) $this->cache->get($this->getOverlappingCounterKey($key), 0);

                if ($count > 0) {
                    return true;
                }

                return $this->getOverlappingLock($key)->get();
            })
            ->containsStrict(false);
    }

    private function releaseLocks(): void
    {
        collect($this->withoutOverlapping)
            ->each(function (string $key): void {
                $this->getOverlappingLock($key)->release();
            });
    }

    private function setCauserToGuard(AuthManager $authManager, ApplicationUserResolver $defaultUserResolver)
    {
        $user = $this->causer instanceof Authenticatable
            ? $this->causer
            : $defaultUserResolver->resolve();

        $authManager->guard()->setUser($user);
    }
}
