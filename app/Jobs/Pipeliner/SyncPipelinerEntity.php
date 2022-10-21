<?php

namespace App\Jobs\Pipeliner;

use App\Events\Pipeliner\QueuedPipelinerSyncEntitySkipped;
use App\Events\Pipeliner\QueuedPipelinerSyncProgress;
use App\Events\Pipeliner\SyncStrategyPerformed;
use App\Integrations\Pipeliner\Exceptions\PipelinerIntegrationException;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\PipelinerSyncBatch;
use App\Services\Pipeliner\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use App\Services\Pipeliner\Strategies\SyncStrategyCollection;
use App\Services\Pipeliner\SyncPipelinerDataStatus;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
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

    public function __construct(
        SyncStrategy $strategy,
        public readonly string $entityReference,
        public readonly ?Model $causer = null,
        public readonly array $withoutOverlapping = [],
    ) {
        $this->strategyClass = $strategy::class;
        $this->units = $strategy->getSalesUnits();
    }

    public function uniqueId(): string
    {
        return 'pipeliner-sync:'.static::class.$this->batchId.$this->entityReference.$this->strategyClass;
    }

    /**
     * Execute the job.
     *
     * @param  SyncStrategyCollection  $strategies
     * @param  SyncPipelinerDataStatus  $status
     * @param  PipelinerSyncBatch  $syncBatch
     * @param  LoggerInterface  $logger
     * @param  EventDispatcher  $eventDispatcher
     * @param  Cache  $cache
     * @param  LockProvider  $lockProvider
     * @return void
     */
    public function handle(
        SyncStrategyCollection $strategies,
        SyncPipelinerDataStatus $status,
        PipelinerSyncBatch $syncBatch,
        LoggerInterface $logger,
        EventDispatcher $eventDispatcher,
        Cache $cache,
        LockProvider $lockProvider,
    ): void {
        if ($this->batch()->canceled()) {
            return;
        }

        $this->cache = $cache;
        $this->lockProvider = $lockProvider;

        if ($this->overlaps()) {
            $logger->debug('Syncing: overlaps', [
                'id' => $this->entityReference,
                'strategy' => class_basename($this->strategyClass),
                'without_overlapping' => $this->withoutOverlapping,
            ]);

            $this->releaseLocks();

            usleep(10 * 1000 * 1000);

            $this->batch()->add([
                    new static(
                        strategy: $strategies[$this->strategyClass],
                        entityReference: $this->entityReference,
                        causer: $this->causer,
                        withoutOverlapping: $this->withoutOverlapping),
                ]
            );
            $this->delete();

            return;
        }

        $syncBatch->id = $this->batchId;

        $this->strategies = $strategies;

        foreach ($strategies as $strategy) {
            $strategy->setSalesUnits(...$this->units);
        }

        $strategy = $strategies[$this->strategyClass];

        $entity = $strategy->getByReference($this->entityReference);

        $logger->info('Syncing: starting', [
            'id' => $entity->id,
            'strategy' => class_basename($this->strategyClass),
        ]);

        try {
            $eventDispatcher->listen(SyncStrategyPerformed::class, $this->incrementOverlappingCounterOnEvent(...));

            $arguments = $this->strategyHasOptions()
                ? [$entity, 'batchId' => $this->batchId]
                : [$entity];

            $result = $strategy->sync(...$arguments);

            if ($strategy instanceof ImpliesSyncOfHigherHierarchyEntities) {
                /** @var SyncStrategy&ImpliesSyncOfHigherHierarchyEntities $strategy */
                $addedJobsCount = $this->syncHigherHierarchyEntities($strategy, $entity, $lockProvider);

                $status->incrementTotal($addedJobsCount);
            }

            $logger->info('Syncing: success', [
                'id' => $entity->id,
                'strategy' => class_basename($this->strategyClass),
            ]);
        } catch (PipelinerIntegrationException|PipelinerSyncException $e) {
            report($e);

            $logger->warning('Syncing: skipped', [
                'id' => $entity->id,
                'error' => trim($e->getMessage()),
                'strategy' => class_basename($this->strategyClass),
            ]);

            $eventDispatcher->dispatch(
                new QueuedPipelinerSyncEntitySkipped(
                    entity: $entity,
                    causer: $this->causer,
                    e: $e,
                )
            );
        } finally {
            $this->releaseLocks();
        }

        $status->incrementProcessed();

        if ($this->batch()->cancelled()) {
            return;
        }

        try {
            $lockProvider->lock(QueuedPipelinerSyncProgress::class, 5)
                ->block(5, static function () use ($status, $eventDispatcher): void {
                    $eventDispatcher->dispatch(
                        new QueuedPipelinerSyncProgress(
                            total: $status->total(),
                            pending: $status->pending(),
                        )
                    );
                });
        } catch (LockTimeoutException) {
        }
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
        LockProvider $lockProvider,
    ): int {
        $jobs = collect();

        foreach ($strategy->resolveHigherHierarchyEntities($relatedEntity) as $entity) {
            $hhStrategies = $this->resolveSuitableStrategiesFor($entity, $strategy);

            foreach ($hhStrategies as $sStrategy) {
                $jobs[] = (new static($sStrategy, $entity->id, $this->causer));
            }
        }

        $jobs = $jobs
            ->filter(function (SyncPipelinerEntity $job) use ($lockProvider): mixed {
                return $lockProvider->lock($job->uniqueId(), 60 * 60 * 8)->get();
            })
            ->values();

        if ($jobs->isNotEmpty()) {
            $this->batch()->add($jobs);
        }

        return $jobs->count();
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
            $this->getOverlappingCounterKey($event->entityReference)
        );
    }

    private function getOverlappingCounterKey(string $key): string
    {
        return static::class.':overlapping-counter'.$this->batchId.$key.$this->strategyClass;
    }

    private function getOverlappingLock(string $key): Lock
    {
        $name = static::class.':overlapping-lock'.$this->batchId.$key.$this->strategyClass;

        return $this->lockProvider->lock($name, owner: $this->job->uuid());
    }

    private function overlaps(): bool
    {
        $keys = collect($this->withoutOverlapping);

        if ($keys->isEmpty()) {
            return false;
        }

        return $keys
            ->map(function (string $key): bool {
                $count = (int)$this->cache->get($this->getOverlappingCounterKey($key), 0);

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
}
