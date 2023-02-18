<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Note\Models\Note;
use App\Domain\Note\Services\NoteDataMapper;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerNoteIntegration;
use App\Domain\Pipeliner\Models\PipelinerModelUpdateLog;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PushStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PushNoteStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface $connection,
                                protected PipelinerNoteIntegration $noteIntegration,
                                protected NoteDataMapper $dataMapper,
                                protected PushClientStrategy $pushClientStrategy,
                                protected LockProvider $lockProvider)
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
     *
     * @throws \App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof Note) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', Note::class));
        }

        if (null !== $model->owner) {
            $this->pushClientStrategy->sync($model->owner);
        }

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateNoteInput($model);

            $entity = $this->noteIntegration->create($input);

            tap($model, function (Note $note) use ($entity): void {
                $note->pl_reference = $entity->id;

                $this->connection->transaction(static fn () => $note->push());
            });
        } else {
            $entity = $this->noteIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateNoteInput($model, $entity);

            if (false === empty($input->getModifiedFields())) {
                $this->noteIntegration->update($input);
            }
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
        return (new Note())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Note;
    }

    public function getByReference(string $reference): object
    {
        return Note::query()->findOrFail($reference);
    }
}
