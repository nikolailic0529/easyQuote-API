<?php

namespace App\Listeners;

use App\Contracts\ProvidesIdForHumans;
use App\Enum\Priority;
use App\Events\Pipeliner\AggregateSyncCompleted;
use App\Events\Pipeliner\AggregateSyncEntityProcessed;
use App\Events\Pipeliner\AggregateSyncEntitySkipped;
use App\Events\Pipeliner\AggregateSyncFailed;
use App\Events\Pipeliner\ModelSyncCompleted;
use App\Events\Pipeliner\SyncStrategyPerformed;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\AppEvent\AppEventEntityService;
use App\Services\Notification\Models\PendingNotification;
use App\Services\Pipeliner\Contracts\ContainsRelatedEntities;
use App\Services\Pipeliner\PipelinerAggregateSyncEventService;
use App\Services\Pipeliner\PipelinerSyncErrorEntityService;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Pipeliner\Strategies\StrategyNameResolver;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

class PipelinerSyncEventSubscriber
{
    public function __construct(
        protected readonly PipelinerSyncErrorEntityService $errorEntityService,
        protected readonly AppEventEntityService $eventEntityService,
        protected readonly PipelinerAggregateSyncEventService $aggregateSyncEventService,
        protected readonly Cache $cache,
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            AggregateSyncEntitySkipped::class => [
                [static::class, 'ensureUnresolvedSyncErrorCreated'],
                [static::class, 'notifyCauserAboutSyncStrategySkipped'],
            ],
            AggregateSyncEntityProcessed::class => [
                [static::class, 'incrementProcessedCount'],
            ],
            SyncStrategyPerformed::class => [
                [static::class, 'resolveRelatedSyncErrors'],
            ],
            ModelSyncCompleted::class => [
                [static::class, 'notifyCauserAboutModelSyncCompleted'],
            ],
            AggregateSyncFailed::class => [
                [static::class, 'storeAggregateSyncFailedEvent'],
            ],
            AggregateSyncCompleted::class => [
                [static::class, 'storeAggregateSyncCompletedEvent'],
            ],
        ];
    }

    public function incrementProcessedCount(AggregateSyncEntityProcessed $event): void
    {
        $reference = $event->entity instanceof Model
            ? $event->entity->pl_reference
            : $event->entity->id;

        $entityType = match ($event->entity::class) {
            Company::class, AccountEntity::class => 'Company',
            Opportunity::class, OpportunityEntity::class => 'Opportunity',
            default => class_basename($event->entity),
        };

        $this->aggregateSyncEventService->incrementUnique(
            $reference,
            $event->aggregateId,
            $entityType,
        );
    }

    public function storeAggregateSyncCompletedEvent(AggregateSyncCompleted $event): void
    {
        $counts = $this->countAggregateSyncProcessed($event->aggregateId);

        $this->eventEntityService->createAppEvent(
            name: 'pipeliner-aggregate-sync-completed',
            occurrence: $event->occurrence,
            payload: [
                'aggregate_id' => $event->aggregateId,
                'success' => true,
                'processed_counts' => $counts,
            ]
        );
    }

    public function storeAggregateSyncFailedEvent(AggregateSyncFailed $event): void
    {
        $counts = $this->countAggregateSyncProcessed($event->aggregateId);

        $this->eventEntityService->createAppEvent(
            name: 'pipeliner-aggregate-sync-completed',
            occurrence: $event->occurrence,
            payload: [
                'aggregate_id' => $event->aggregateId,
                'success' => false,
                'processed_counts' => $counts,
            ]
        );
    }

    public function resolveRelatedSyncErrors(SyncStrategyPerformed $event): void
    {
        $this->errorEntityService->markRelatedSyncErrorsResolved(
            model: $event->model,
            strategy: StrategyNameResolver::from($event->strategyClass)
        );
    }

    public function ensureUnresolvedSyncErrorCreated(AggregateSyncEntitySkipped $event): void
    {
        $relatedModels = Collection::empty();
        $strategy = StrategyNameResolver::from($event->strategy);

        if (is_a($event->strategy, PushStrategy::class, true)) {
            /** @var Model $model */
            $model = $event->entity;

            $relatedModels->push($event->entity);

            $errorMessage = $this->renderErrorMessageForModel($model, $event->e);
        } else {
            $errorMessage = $this->renderErrorMessageForEntity($event->entity, $event->e);

            $model = $this->resolvePipelinerEntityRelatedModel($event->entity);

            if ($model !== null) {
                $relatedModels->push($model);
            } elseif ($event->e instanceof ContainsRelatedEntities) {
                collect($event->e->getRelated())
                    ->lazy()
                    ->whereInstanceOf(Model::class)
                    ->each(function (Model $model) use ($relatedModels): void {
                        $relatedModels->push($model);
                    });
            }
        }

        $relatedModels
            ->each(function (Model $model) use ($strategy, $errorMessage): void {
                $this->errorEntityService->ensureSyncErrorCreatedForMessage(
                    model: $model,
                    strategy: $strategy,
                    message: $errorMessage
                );
            });
    }

    public function notifyCauserAboutModelSyncCompleted(ModelSyncCompleted $event): void
    {
        if ($event->causer instanceof User) {
            $modelName = Str::headline(class_basename($event->model));
            $modelIdForHumans = $this->modelIdForHumans($event->model);
            $url = $this->resolveUrlToModel($event->model);

            notification()
                ->for($event->causer)
                ->priority(Priority::Low)
                ->unless(null === $url, static function (PendingNotification $n) use ($url): void {
                    $n->url($url);
                })
                ->message("Data sync of $modelName [$modelIdForHumans] has been completed.")
                ->push();
        }
    }

    public function notifyCauserAboutSyncStrategySkipped(AggregateSyncEntitySkipped $event): void
    {
        if ($event->entity instanceof Model) {
            $this->notifyCauserAboutSyncStrategyModelSkipped($event);
        } else {
            $this->notifyCauserAboutSyncStrategyEntitySkipped($event);
        }
    }

    private function notifyCauserAboutSyncStrategyModelSkipped(AggregateSyncEntitySkipped $event): void
    {
        /** @var Model $model */
        $model = $event->entity;

        $errorMessage = $this->renderErrorMessageForModel($model, $event->e);

        if ($event->causer instanceof User) {
            $url = $this->resolveUrlToModel($model);

            notification()
                ->for($event->causer)
                ->priority(Priority::High)
                ->unless(is_null($url), static function (PendingNotification $n) use ($url): void {
                    $n->url($url);
                })
                ->message($errorMessage)
                ->push();
        }
    }

    private function notifyCauserAboutSyncStrategyEntitySkipped(AggregateSyncEntitySkipped $event): void
    {
        $errorMessage = $this->renderErrorMessageForEntity($event->entity, $event->e);

        $relatedModels = $event->e instanceof ContainsRelatedEntities
            ? collect($event->e->getRelated())->whereInstanceOf(Model::class)->values()
            : collect();

        if ($event->causer instanceof User) {
            $relatedModel = $relatedModels->first();

            $url = isset($relatedModel) ? $this->resolveUrlToModel($relatedModel) : null;

            notification()
                ->for($event->causer)
                ->priority(Priority::High)
                ->unless(is_null($url), static function (PendingNotification $n) use ($url): void {
                    $n->url($url);
                })
                ->message($errorMessage)
                ->push();
        }
    }

    private function resolvePipelinerEntityRelatedModel(object $entity): ?Model
    {
        return match ($entity::class) {
            OpportunityEntity::class => (static function () use ($entity): ?Opportunity {
                return Opportunity::query()->withTrashed()
                    ->where('pl_reference', $entity->id)
                    ->latest((new Opportunity())->getUpdatedAtColumn())
                    ->first();
            })(),
            AccountEntity::class => (static function () use ($entity): ?Company {
                return Company::query()->withTrashed()
                    ->where('pl_reference', $entity->id)
                    ->latest((new Company())->getUpdatedAtColumn())
                    ->first();
            })(),
            default => null,
        };
    }

    #[ArrayShape(['opportunities' => "int", 'companies' => "int"])]
    private function countAggregateSyncProcessed(string $aggregateId): array
    {
        $pendingCounts = [];

        $pendingCounts['opportunities'] = $this->aggregateSyncEventService->count($aggregateId, 'Opportunity');
        $pendingCounts['companies'] = $this->aggregateSyncEventService->count($aggregateId, 'Company');

        return [
            'opportunities' => $pendingCounts['opportunities'],
            'companies' => $pendingCounts['companies'],
        ];
    }

    private function resolveUrlToModel(Model $model): ?string
    {
        return match ($model::class) {
            Opportunity::class => ui_route('opportunities.update', ['opportunity' => $model]),
            Company::class => ui_route('companies.update', ['company' => $model]),
            default => null,
        };
    }

    private function modelIdForHumans(Model $model): string
    {
        if ($model instanceof ProvidesIdForHumans) {
            return $model->getIdForHumans();
        }

        return $model->getKey();
    }

    private function entityIdForHumans(object $entity): string
    {
        if ($entity instanceof OpportunityEntity) {
            return $entity->name;
        }

        if ($entity instanceof AccountEntity) {
            return $entity->name;
        }

        return $entity->id;
    }

    private function errorsForHumans(\Throwable $e): BaseCollection
    {
        if ($e instanceof GraphQlRequestException) {
            return collect($e->errors)
                ->lazy()
                ->filter(static function (array $error): bool {
                    return isset($error['api_error']);
                })
                ->map(static function (array $error) {
                    $apiError = $error['api_error'];

                    $errorName = Str::of($apiError['name'])->lower()->headline()->__toString();
                    $errorMessage = trim($apiError['message']);

                    return "$errorName: $errorMessage";
                })
                ->eager()
                ->pipe(static function (LazyCollection $collection): BaseCollection {
                    return collect($collection->all());
                });
        }

        return collect([$e->getMessage()]);
    }

    private function renderErrorMessageForModel(Model $model, ?\Throwable $e): string
    {
        $modelName = class_basename($model);

        $errors = isset($e) ? $this->errorsForHumans($e) : null;

        return Blade::render(<<<MSG
Unable push {{ \$model_name }} [{{ \$model_id }}] to pipeliner due to errors.
@isset(\$errors)
{!! \$errors !!}@endisset
MSG,
            [
                'model_id' => $this->modelIdForHumans($model),
                'model_name' => $modelName,
                'errors' => $errors?->join("\n"),
            ]);
    }

    private function renderErrorMessageForEntity(object $entity, ?\Throwable $e): string
    {
        $entityName = Str::of($entity::class)->classBasename()->beforeLast('Entity')->headline();

        $errors = isset($e) ? $this->errorsForHumans($e) : null;

        return Blade::render(<<<MSG
Unable pull {{ \$entity_name }} [{{ \$entity_id }}] from pipeliner due to errors.
@isset(\$errors)
{!! \$errors !!}@endisset
MSG,
            [
                'entity_id' => $this->entityIdForHumans($entity),
                'entity_name' => $entityName,
                'errors' => $errors?->join("\n"),
            ]);
    }
}