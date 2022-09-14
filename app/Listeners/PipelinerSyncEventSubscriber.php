<?php

namespace App\Listeners;

use App\Contracts\ProvidesIdForHumans;
use App\Enum\Priority;
use App\Events\Pipeliner\QueuedPipelinerSyncLocalEntitySkipped;
use App\Events\Pipeliner\QueuedPipelinerSyncRemoteEntitySkipped;
use App\Integrations\Pipeliner\Exceptions\GraphQlRequestException;
use App\Models\Appointment\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Task\Task;
use App\Models\User;
use App\Services\Notification\Models\PendingNotification;
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
        $events->listen(QueuedPipelinerSyncLocalEntitySkipped::class, [self::class, 'handleLocalEntitySkippedEvent']);
        $events->listen(QueuedPipelinerSyncRemoteEntitySkipped::class, [self::class, 'handleRemoteEntitySkippedEvent']);
    }

    public function handleLocalEntitySkippedEvent(QueuedPipelinerSyncLocalEntitySkipped $event): void
    {
        if ($event->causer instanceof User) {
            $url = match ($event->model::class) {
                Opportunity::class => ui_route('opportunities.update', ['opportunity' => $event->model]),
                Company::class => ui_route('companies.update', ['company' => $event->model]),
                default => null,
            };

            $modelName = Str::headline(class_basename($event->model));

            $errors = isset($event->e) ? $this->errorsForHumans($event->e) : null;

            notification()
                ->for($event->causer)
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
                            'model_id' => $this->modelIdForHumans($event->model),
                            'model_name' => $modelName,
                            'errors' => $errors->join("\n"),
                        ])
                )
                ->push();
        }
    }

    public function handleRemoteEntitySkippedEvent(QueuedPipelinerSyncRemoteEntitySkipped $event): void
    {
        if ($event->causer instanceof User) {

            $entityName = Str::of($event->entity)->classBasename()->beforeLast('Entity')->headline();

            notification()
                ->for($event->causer)
                ->priority(Priority::High)
                ->message("Unable to pull $entityName ({$event->entity->id}) from pipeliner due to errors.")
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