<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\CloudObjectIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerTaskIntegration;
use App\Integrations\Pipeliner\Models\TaskEntity;
use App\Models\Attachment;
use App\Models\Pipeline\Pipeline;
use App\Models\PipelinerModelUpdateLog;
use App\Models\Task\Task;
use App\Models\Task\TaskReminder;
use App\Services\Attachment\AttachmentDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use App\Services\Task\TaskDataMapper;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PushTaskStrategy implements PushStrategy
{
    protected ?Pipeline $pipeline = null;

    public function __construct(protected ConnectionInterface      $connection,
                                protected PushClientStrategy       $clientStrategy,
                                protected PipelinerTaskIntegration $taskIntegration,
                                protected TaskDataMapper           $dataMapper,
                                protected CloudObjectIntegration   $cloudObjectIntegration,
                                protected AttachmentDataMapper     $attachmentDataMapper,
                                protected LockProvider             $lockProvider)
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
     * @return void
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (null !== $model->user) {
            $this->clientStrategy->sync($model->user);
        }

        $this->pushAttachmentsOfTask($model);

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateTaskInput($model);

            $entity = $this->taskIntegration->create($input);

            tap($model, function (Task $task) use ($entity): void {
                $task->pl_reference = $entity->id;

                $this->connection->transaction(static fn() => $task->push());
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
            $this->pushAttachment($attachment);
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

                $this->connection->transaction(static fn() => $reminder->save());
            });
        } else {
            $result = $this->taskIntegration->removeReminder(
                $this->dataMapper->mapPipelinerRemoveReminderTaskInput($task)
            );
        }
    }

    public function pushAttachment(Attachment $attachment): void
    {
        if (null === $attachment->pl_reference) {
            $input = $this->attachmentDataMapper->mapPipelinerCreateCloudObjectInput($attachment);

            $entity = $this->cloudObjectIntegration->create($input);

            tap($attachment, function (Attachment $attachment) use ($entity): void {
                $attachment->pl_reference = $entity->id;

                $this->connection->transaction(static fn() => $attachment->save());
            });
        }
    }

    public function setPipeline(Pipeline $pipeline): static
    {
        return tap($this, fn() => $this->pipeline = $pipeline);
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function countPending(): int
    {
        $this->pipeline ?? throw PipelinerSyncException::unsetPipeline();

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
}