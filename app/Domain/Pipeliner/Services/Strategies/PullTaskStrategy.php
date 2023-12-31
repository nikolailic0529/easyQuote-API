<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Attachment\Services\AttachmentDataMapper;
use App\Domain\Attachment\Services\AttachmentFileService;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerTaskIntegration;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\SalesUnitFilterInput;
use App\Domain\Pipeliner\Integration\Models\TaskEntity;
use App\Domain\Pipeliner\Integration\Models\TaskFilterInput;
use App\Domain\Pipeliner\Models\PipelinerModelScrollCursor;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PullStrategy;
use App\Domain\Task\Models\Task;
use App\Domain\Task\Services\TaskDataMapper;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use JetBrains\PhpStorm\ArrayShape;

class PullTaskStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(
        protected ConnectionInterface $connection,
        protected PipelinerTaskIntegration $taskIntegration,
        protected TaskDataMapper $dataMapper,
        protected AttachmentDataMapper $attachmentDataMapper,
        protected AttachmentFileService $attachmentFileService,
        protected PullAttachmentStrategy $pullAttachmentStrategy,
        protected LockProvider $lockProvider
    ) {
    }

    /**
     * @param TaskEntity $entity
     *
     * @throws \Throwable
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof TaskEntity) {
            throw new \TypeError(sprintf('Entity must be an instance of %s.', TaskEntity::class));
        }

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
        $attachments = collect($entity->documents)
            ->lazy()
            ->chunk(50)
            ->map(function (LazyCollection $collection): array {
                return $this->pullAttachmentStrategy->batch(...$collection->all());
            })
            ->collapse()
            ->pipe(static function (LazyCollection $collection) {
                return Collection::make($collection->all());
            });

        if ($attachments->isNotEmpty()) {
            $this->connection->transaction(
                static fn () => $model->attachments()->syncWithoutDetaching($attachments)
            );
        }
    }

    public function syncByReference(string $reference): Model
    {
        return $this->sync(
            $this->taskIntegration->getById($reference)
        );
    }

    public function countPending(): int
    {
        [$count, $lastId] = $this->computeTotalEntitiesCountAndLastIdToPull();

        return $count;
    }

    public function iteratePending(): \Traversable
    {
        return LazyCollection::make(function (): \Generator {
            yield from $this->taskIntegration->simpleScroll(
                ...$this->resolveScrollParameters()
            );
        })
            ->values();
    }

    public function getModelType(): string
    {
        return (new Task())->getMorphClass();
    }

    private function computeTotalEntitiesCountAndLastIdToPull(): array
    {
        /** @var \Generator $iterator */
        $iterator = $this->taskIntegration->simpleScroll(
            ...$this->resolveScrollParameters(),
            ...['first' => 1_000]
        );

        $totalCount = 0;
        $lastId = null;

        while ($iterator->valid()) {
            $lastId = $iterator->current();
            ++$totalCount;

            $iterator->next();
        }

        return [$totalCount, $lastId];
    }

    #[ArrayShape(['after' => 'string|null', 'filter' => TaskFilterInput::class])]
    private function resolveScrollParameters(): array
    {
        $filter = TaskFilterInput::new();
        $unitFilter = SalesUnitFilterInput::new()->name(EntityFilterStringField::eq(
            ...collect($this->getSalesUnits())->pluck('unit_name')
        ));
        $filter->unit($unitFilter);

        return [
            'after' => $this->getMostRecentScrollCursor()?->cursor,
            'filter' => $filter,
        ];
    }

    private function getMostRecentScrollCursor(): ?PipelinerModelScrollCursor
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Task;
    }

    #[ArrayShape([
        'id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class,
    ])]
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

    public function getByReference(string $reference): TaskEntity
    {
        return $this->taskIntegration->getById($reference);
    }
}
