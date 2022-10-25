<?php

namespace App\Listeners;

use App\Contracts\ProvidesIdForHumans;
use App\Enum\Priority;
use App\Events\Pipeliner\ModelSyncCompleted;
use App\Events\Pipeliner\SyncStrategyEntitySkipped;
use App\Events\Pipeliner\SyncStrategyPerformed;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Notification\Models\PendingNotification;
use App\Services\Pipeliner\Contracts\ContainsRelatedEntities;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\PipelinerSyncErrorEntityService;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Pipeliner\Strategies\StrategyNameResolver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class PipelinerSyncEventSubscriber implements ShouldQueue
{
    public function __construct(
        protected readonly PipelinerSyncErrorEntityService $errorEntityService,
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            SyncStrategyEntitySkipped::class => [
                [static::class, 'ensureUnresolvedSyncErrorCreated'],
                [static::class, 'notifyCauserAboutSyncStrategySkipped'],
            ],
            SyncStrategyPerformed::class => [
                [static::class, 'resolveRelatedSyncErrors'],
            ],
            ModelSyncCompleted::class => [
                [static::class, 'notifyCauserAboutModelSyncCompleted'],
            ],
        ];
    }

    public function ensureUnresolvedSyncErrorCreated(SyncStrategyEntitySkipped $event): void
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

            if ($event->e instanceof ContainsRelatedEntities) {
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

    public function resolveRelatedSyncErrors(SyncStrategyPerformed $event): void
    {
        $this->errorEntityService->markRelatedSyncErrorsResolved(
            entityId: $event->entityReference,
            strategy: StrategyNameResolver::from($event->strategyClass)
        );
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

    public function notifyCauserAboutSyncStrategySkipped(SyncStrategyEntitySkipped $event): void
    {
        if ($event->entity instanceof Model) {
            $this->notifyCauserAboutSyncStrategyModelSkipped($event);
        } else {
            $this->notifyCauserAboutSyncStrategyEntitySkipped($event);
        }
    }

    protected function notifyCauserAboutSyncStrategyModelSkipped(SyncStrategyEntitySkipped $event): void
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

    protected function notifyCauserAboutSyncStrategyEntitySkipped(SyncStrategyEntitySkipped $event): void
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

    protected function resolveUrlToModel(Model $model): ?string
    {
        return match ($model::class) {
            Opportunity::class => ui_route('opportunities.update', ['opportunity' => $model]),
            Company::class => ui_route('companies.update', ['company' => $model]),
            default => null,
        };
    }

    protected function modelIdForHumans(Model $model): string
    {
        if ($model instanceof ProvidesIdForHumans) {
            return $model->getIdForHumans();
        }

        return $model->getKey();
    }

    protected function entityIdForHumans(object $entity): string
    {
        if ($entity instanceof OpportunityEntity) {
            return $entity->name;
        }

        if ($entity instanceof AccountEntity) {
            return $entity->name;
        }

        return $entity->id;
    }

    protected function pipelinerErrorsForHumans(\Throwable $e): BaseCollection
    {
        if ($e instanceof PipelinerSyncException) {
            return collect([$e->getMessage()]);
        }

        return BaseCollection::empty();
    }

    protected function errorsForHumans(\Throwable $e): BaseCollection
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

    protected function renderErrorMessageForModel(Model $model, ?\Throwable $e): string
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

    protected function renderErrorMessageForEntity(object $entity, ?\Throwable $e): string
    {
        $entityName = Str::of($entity::class)->classBasename()->beforeLast('Entity')->headline();

        $errors = isset($event->e) ? $this->pipelinerErrorsForHumans($e) : null;

        return Blade::render(<<<MSG
Unable pull {{ \$entity_name }} [{{ \$entity_id }}] from pipeliner due to errors.
@isset(\$errors)
{!! \$errors !!}@endisset
MSG,
            [
                'entity_id' => $this->entityIdForHumans($entity),
                'entity_name' => $entityName,
                'errors' => isset($errors) && $errors->isNotEmpty() ? $errors->join("\n") : null,
            ]);
    }
}