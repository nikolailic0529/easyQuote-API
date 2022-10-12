<?php

namespace App\Listeners;

use App\Contracts\ProvidesIdForHumans;
use App\Enum\Priority;
use App\Events\Pipeliner\QueuedPipelinerSyncEntitySkipped;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Integrations\Pipeliner\Models\AccountEntity;
use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Notification\Models\PendingNotification;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class PipelinerSyncEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(QueuedPipelinerSyncEntitySkipped::class, [self::class, 'handleEntitySkippedEvent']);
    }

    public function handleEntitySkippedEvent(QueuedPipelinerSyncEntitySkipped $event): void
    {
        if ($event->entity instanceof Model) {
            $this->handleModelSkippedEvent($event->entity, $event->e, $event->causer);
        } else {
            $this->handlePipelinerEntitySkippedEvent($event->entity, $event->e, $event->causer);
        }
    }

    protected function handleModelSkippedEvent(Model $model, ?\Throwable $e, ?Model $causer): void
    {
        if ($causer instanceof User) {
            $url = match ($model::class) {
                Opportunity::class => ui_route('opportunities.update', ['opportunity' => $model]),
                Company::class => ui_route('companies.update', ['company' => $model]),
                default => null,
            };

            $modelName = Str::headline(class_basename($model));

            $errors = isset($e) ? $this->errorsForHumans($e) : null;

            notification()
                ->for($causer)
                ->priority(Priority::High)
                ->unless(is_null($url), static function (PendingNotification $n) use ($url): void {
                    $n->url($url);
                })
                ->message(
                    Blade::render(<<<MSG
Unable push {{ \$model_name }} [{{ \$model_id }}] to pipeliner due to errors.
@isset(\$errors)
{!! \$errors !!}@endisset
MSG,
                        [
                            'model_id' => $this->modelIdForHumans($model),
                            'model_name' => $modelName,
                            'errors' => $errors?->join("\n"),
                        ])
                )
                ->push();
        }
    }

    protected function handlePipelinerEntitySkippedEvent(object $entity, ?\Throwable $e, ?Model $causer): void
    {
        if ($causer instanceof User) {
            $entityName = Str::of($entity::class)->classBasename()->beforeLast('Entity')->headline();

            $errors = isset($e) ? $this->pipelinerErrorsForHumans($e) : null;

            notification()
                ->for($causer)
                ->priority(Priority::High)
                ->message(
                    Blade::render(<<<MSG
Unable pull {{ \$entity_name }} [{{ \$entity_id }}] from pipeliner due to errors.
@isset(\$errors)
{!! \$errors !!}@endisset
MSG,
                        [
                            'entity_id' => $this->entityIdForHumans($entity),
                            'entity_name' => $entityName,
                            'errors' => isset($errors) && $errors->isNotEmpty() ? $errors->join("\n") : null,
                        ])
                )
                ->push();
        }
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

    protected function pipelinerErrorsForHumans(\Throwable $e): Collection
    {
        if ($e instanceof PipelinerSyncException) {
            return collect([$e->getMessage()]);
        }

        return Collection::empty();
    }

    protected function errorsForHumans(\Throwable $e): Collection
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
                ->pipe(static function (LazyCollection $collection): Collection {
                    return collect($collection->all());
                });
        }

        return collect([$e->getMessage()]);
    }
}