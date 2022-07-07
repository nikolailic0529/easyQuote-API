<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerNoteIntegration;
use App\Models\Note\Note;
use App\Models\Pipeline\Pipeline;
use App\Models\PipelinerModelUpdateLog;
use App\Services\Note\NoteDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PushNoteStrategy implements PushStrategy
{
    protected ?Pipeline $pipeline = null;

    public function __construct(protected ConnectionInterface      $connection,
                                protected PipelinerNoteIntegration $noteIntegration,
                                protected NoteDataMapper           $dataMapper,
                                protected PushClientStrategy       $pushClientStrategy,
                                protected LockProvider             $lockProvider)
    {
    }

    private function modelsToBeUpdatedQuery(): Builder
    {
        $lastUpdatedAt = PipelinerModelUpdateLog::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Note();

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
     * @param Note $model
     * @return void
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (null === $model->pl_reference) {
            if (null !== $model->owner) {
                $this->pushClientStrategy->sync($model->owner);
            }

            $input = $this->dataMapper->mapPipelinerCreateNoteInput($model);

            $entity = $this->noteIntegration->create($input);

            tap($model, function (Note $note) use ($entity): void {
                $note->pl_reference = $entity->id;

                $this->connection->transaction(static fn() => $note->push());
            });
        } else {
            if (null !== $model->owner) {
                $this->pushClientStrategy->sync($model->owner);
            }

            $entity = $this->noteIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateNoteInput($model, $entity);

            if (false === empty($input->getModifiedFields())) {
                $this->noteIntegration->update($input);
            }
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
        return (new Note())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Note;
    }
}