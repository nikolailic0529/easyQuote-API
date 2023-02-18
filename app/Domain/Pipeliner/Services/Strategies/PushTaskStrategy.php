<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Attachment\Services\AttachmentDataMapper;
use App\Domain\Pipeliner\Integration\GraphQl\CloudObjectIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerTaskIntegration;
use App\Domain\Pipeliner\Integration\Models\TaskEntity;
use App\Domain\Pipeliner\Models\PipelinerModelUpdateLog;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PushStrategy;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Models\TaskReminder;
use App\Domain\Task\Services\TaskDataMapper;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PushTaskStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface $connection,
                                protected PushClientStrategy $clientStrategy,
                                protected PipelinerTaskIntegration $taskIntegration,
                                protected TaskDataMapper $dataMapper,
                                protected CloudObjectIntegration $cloudObjectIntegration,
                                protected AttachmentDataMapper $attachmentDataMapper,
                                protected PushSalesUnitStrategy $pushSalesUnitStrategy,
                                protected PushAttachmentStrategy $pushAttachmentStrategy,
                                protected LockProvider $lockProvider)
    {
    }

    private function modelsToBeUpdatedQuery(): Builder
    {
        $lastUpdatedAt = PipelinerModelUpdateLog::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Task();

        return $model->newQuery()
            ->orderBy($model->getQualifiedUpdatedAtColumn())
            ->whereIn($model->salesUnit()->getQualifiedForeignKeyName(), Collection::make($this->getSalesUnits())->modelKeys())
            ->where(static function (Builder $builder) use ($model): void {
                $builder->whereColumn($model->getQualifiedUpdatedAtColumn(), '>', $model->getQualifiedCreatedAtColumn())
                    ->orWhereNull($model->qualifyColumn('pl_reference'));
            })
            ->unless(is_null($lastUpdatedAt), static function (Builder $builder) use ($model, $lastUpdatedAt): void {
                $builder->where($model->getQualifiedUpdatedAtColumn(), '>', $lastUpdatedAt);
            });
    }

    /**
     * @param Task $model
     *
     * @throws \App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof Task) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', Task::class));
        }

        if (null !== $model->user) {
            $this->clientStrategy->sync($model->user);
        }

        if (null !== $model->salesUnit) {
            $this->pushSalesUnitStrategy->sync($model->salesUnit);
        }

        $this->pushAttachmentsOfTask($model);

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateTaskInput($model);

            $entity = $this->taskIntegration->create($input);

            tap($model, function (Task $task) use ($entity): void {
                $task->pl_reference = $entity->id;

                $this->connection->transaction(static fn () => $task->push());
            });
        } else {
            $entity = $this->taskIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateTaskInput($model, $entity);

            $this->taskIntegration->update($input);

            $this->pushReminderOfTask($model, $entity);
        }
    }

    public function pushAttachmentsOfTask(Task $task): void
    {
        foreach ($task->attachments as $attachment) {
            $this->pushAttachmentStrategy->sync($attachment);
        }
    }

    public function pushReminderOfTask(Task $task, TaskEntity $entity): void
    {
        if ($entity->reminder?->id === $task->reminder?->pl_reference) {
            return;
        }

        if (null !== $task->reminder) {
            $result = $this->taskIntegration->setReminder(
                $this->dataMapper->mapPipelinerSetReminderTaskInput($task->reminder, $task)
            );

            tap($task->reminder, function (TaskReminder $reminder) use ($result) {
                $reminder->pl_reference = $result->reminder->id;

                $this->connection->transaction(static fn () => $reminder->save());
            });
        } else {
            $result = $this->taskIntegration->removeReminder(
                $this->dataMapper->mapPipelinerRemoveReminderTaskInput($task)
            );
        }
    }

    public function countPending(): int
    {
        return $this->modelsToBeUpdatedQuery()->count();
    }

    public function iteratePending(): \Traversable
    {
        return $this->modelsToBeUpdatedQuery()->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new Task())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Task;
    }

    public function getByReference(string $reference): object
    {
        return Task::query()->findOrFail($reference);
    }
}
