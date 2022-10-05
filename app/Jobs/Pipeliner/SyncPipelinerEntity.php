<?php

namespace App\Jobs\Pipeliner;

use App\Events\Pipeliner\QueuedPipelinerSyncEntitySkipped;
use App\Events\Pipeliner\QueuedPipelinerSyncProgress;
use App\Integrations\Pipeliner\Exceptions\PipelinerIntegrationException;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\ImpliesSyncOfHigherHierarchyEntities;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Pipeliner\Strategies\Contracts\SyncStrategy;
use App\Services\Pipeliner\Strategies\StrategyNameResolver;
use App\Services\Pipeliner\Strategies\SyncStrategyCollection;
use App\Services\Pipeliner\SyncPipelinerDataStatus;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
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

    protected SyncStrategyCollection $strategies;

    public function __construct(
        SyncStrategy $strategy,
        public readonly string $entityReference,
        public readonly ?Model $causer,
    ) {
        $this->strategyClass = $strategy::class;
        $this->units = $strategy->getSalesUnits();
    }

    /**
     * Execute the job.
     *
     * @param  SyncStrategyCollection  $strategies
     * @param  SyncPipelinerDataStatus  $status
     * @param  LoggerInterface  $logger
     * @param  EventDispatcher  $eventDispatcher
     * @return void
     */
    public function handle(
        SyncStrategyCollection $strategies,
        SyncPipelinerDataStatus $status,
        LoggerInterface $logger,
        EventDispatcher $eventDispatcher,
    ): void {
        if ($this->batch()->canceled()) {
            return;
        }

        $this->strategies = $strategies;

        foreach ($strategies as $strategy) {
            $strategy->setSalesUnits(...$this->units);
        }

        $strategy = $strategies[$this->strategyClass];

        $entity = $strategy->getByReference($this->entityReference);

        $logger->info(sprintf('Syncing [%s]: starting', class_basename($entity)), [
            'id' => $entity->id,
            'strategy' => class_basename($this->strategyClass),
        ]);

        try {
            $result = $strategy->sync($entity);

            if ($strategy instanceof ImpliesSyncOfHigherHierarchyEntities) {
                /** @var SyncStrategy&ImpliesSyncOfHigherHierarchyEntities $strategy */
                $addedJobsCount = $this->syncHigherHierarchyEntities($strategy, $entity);

                $status->incrementTotal($addedJobsCount);
            }

            $model = $strategy instanceof PullStrategy
                ? $result
                : $entity;

            $this->persistSyncStrategyLog($model, $strategy);

            $logger->info(sprintf('Syncing [%s]: success', class_basename($entity)), [
                'id' => $entity->id,
                'strategy' => class_basename($this->strategyClass),
            ]);
        } catch (PipelinerIntegrationException|PipelinerSyncException $e) {
            report($e);

            $logger->warning(sprintf('Syncing [%s]: skipped', class_basename($entity)), [
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
        }

        $status->incrementProcessed();

        $eventDispatcher->dispatch(
            new QueuedPipelinerSyncProgress(
                total: $status->total(),
                pending: $status->pending(),
            )
        );
    }

    private function persistSyncStrategyLog(Model $model, SyncStrategy $strategy): void
    {
        tap(new PipelinerSyncStrategyLog(),
            static function (PipelinerSyncStrategyLog $log) use ($model, $strategy): void {
                $log->model()->associate($model);
                $log->strategy_name = (string) StrategyNameResolver::from($strategy);

                $log->save();
            });
    }

    protected function syncHigherHierarchyEntities(
        SyncStrategy&ImpliesSyncOfHigherHierarchyEntities $strategy,
        object $relatedEntity,
    ): int {
        $jobs = collect();

        foreach ($strategy->resolveHigherHierarchyEntities($relatedEntity) as $entity) {
            $hhStrategies = $this->resolveSuitableStrategiesFor($entity, $strategy);

            foreach ($hhStrategies as $sStrategy) {
                $jobs[] = new static($sStrategy, $entity->id, $this->causer);
            }
        }

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
}
