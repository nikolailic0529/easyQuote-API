<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerTaskIntegration;
use App\Integrations\Pipeliner\Models\CloudObjectEntity;
use App\Integrations\Pipeliner\Models\TaskEntity;
use App\Models\Attachment;
use App\Models\Pipeline\Pipeline;
use App\Models\PipelinerModelScrollCursor;
use App\Models\Task\Task;
use App\Services\Attachment\AttachmentDataMapper;
use App\Services\Attachment\AttachmentFileService;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use App\Services\Task\TaskDataMapper;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

class PullTaskStrategy implements PullStrategy
{
    protected ?Pipeline $pipeline = null;

    public function __construct(protected ConnectionInterface      $connection,
                                protected PipelinerTaskIntegration $taskIntegration,
                                protected TaskDataMapper           $dataMapper,
                                protected AttachmentDataMapper     $attachmentDataMapper,
                                protected AttachmentFileService    $attachmentFileService,
                                protected LockProvider             $lockProvider)
    {
    }

    /**
     * @param TaskEntity $entity
     * @return Model
     * @throws \Throwable
     */
    public function sync(object $entity): Model
    {
        /** @var Task|null $task */
        $task = Task::query()
            ->withTrashed()
            ->where('pl_reference', $entity->id)
            ->first();

        if (null !== $task) {
            $updatedTask = $this->dataMapper->mapFromTaskEntity($entity);

            $this->dataMapper->mergeAttributeFrom($task, $updatedTask);

            $this->connection->transaction(static function () use ($task): void {
                $task->withoutTimestamps(static function (Task $task): void {
                    $task->save(['touch' => false]);

                    $task->reminder?->save();
                    $task->recurrence?->save();

                    $task->companies()->sync($task->companies);
                    $task->contacts()->sync($task->contacts);

                    $task->opportunities()->sync($task->opportunities);
                });
            });

            $this->syncRelationsOfTaskEntity($entity, $task);

            return $task;
        }

        $task = $this->dataMapper->mapFromTaskEntity($entity);

        $this->connection->transaction(static function () use ($task): void {
            $task->user?->save();

            $task->save();

            $task->reminder?->save();
            $task->recurrence?->save();

            $task->companies()->attach($task->companies);
            $task->contacts()->attach($task->contacts);
            $task->opportunities()->attach($task->opportunities);
        });

        $this->syncRelationsOfTaskEntity($entity, $task);

        return $task;
    }

    private function syncRelationsOfTaskEntity(TaskEntity $entity, Task $model): void
    {
        foreach ($entity->documents as $document) {
            $this->syncDocument($document, $model);
        }
    }

    private function syncDocument(CloudObjectEntity $entity, Task $model): void
    {
        /** @var Attachment|null $attachment */
        $attachment = Attachment::query()
            ->where('pl_reference', $entity->id)
            ->withTrashed()
            ->first();

        // skip if already exists
        if (null !== $attachment) {
            return;
        }

        $metadata = $this->attachmentFileService->downloadFromUrl($entity->url);

        tap($this->attachmentDataMapper->mapFromCloudObjectEntity($entity, $metadata), function (Attachment $attachment) use ($model): void {
            $this->connection->transaction(static function () use ($model, $attachment): void {
                $attachment->save();
                $model->attachments()->syncWithoutDetaching($attachment);
            });
        });
    }

    public function syncByReference(string $reference): Model
    {
        return $this->sync(
            $this->taskIntegration->getById($reference)
        );
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
        $mostRecentCursor = $this->getMostRecentScrollCursor();

        [$count, $lastId] = $this->computeTotalEntitiesCountAndLastIdToPull($mostRecentCursor);

        return $count;
    }

    public function iteratePending(): \Traversable
    {
        $latestCursor = $this->getMostRecentScrollCursor();

        return $this->taskIntegration->scroll(
            after: $latestCursor?->cursor,
        );
    }

    public function getModelType(): string
    {
        return (new Task())->getMorphClass();
    }

    private function computeTotalEntitiesCountAndLastIdToPull(PipelinerModelScrollCursor $scrollCursor = null): array
    {
        /** @var \Generator $iterator */
        $iterator = $this->taskIntegration->simpleScroll(
            after: $scrollCursor?->cursor,
            first: 1000
        );

        $totalCount = 0;
        $lastId = null;

        while ($iterator->valid()) {
            $lastId = $iterator->current();
            $totalCount++;

            $iterator->next();
        }

        return [$totalCount, $lastId];
    }

    private function getMostRecentScrollCursor(): ?PipelinerModelScrollCursor
    {
        $this->pipeline ?? throw PipelinerSyncException::unsetPipeline();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->whereBelongsTo($this->pipeline)
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Task;
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class, 'modified' => \DateTimeInterface::class])]
    public function getMetadata(string $reference): array
    {
        $entity = $this->taskIntegration->getById($reference);

        return [
            'id' => $entity->id,
            'revision' => $entity->revision,
            'created' => $entity->created,
            'modified' => $entity->modified,
        ];
    }
}