<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerNoteIntegration;
use App\Integrations\Pipeliner\Models\NoteEntity;
use App\Models\Note\Note;
use App\Models\PipelinerModelScrollCursor;
use App\Services\Note\NoteDataMapper;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

class PullNoteStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface      $connection,
                                protected PipelinerNoteIntegration $noteIntegration,
                                protected NoteDataMapper           $dataMapper,
                                protected LockProvider             $lockProvider)
    {
    }

    /**
     * @param NoteEntity $entity
     * @return Model
     * @throws \Throwable
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof NoteEntity) {
            throw new \TypeError(sprintf("Entity must be an instance of %s.", NoteEntity::class));
        }

        /** @var Note|null $note */
        $note = Note::query()
            ->withTrashed()
            ->where('pl_reference', $entity->id)
            ->first();

        if (null !== $note) {
            $updatedNote = $this->dataMapper->mapFromNoteEntity($entity);

            $this->dataMapper->mergeAttributesFrom($note, $updatedNote);

            $this->connection->transaction(static function () use ($note): void {
                $note->withoutTimestamps(static function (Note $note): void {
                    $note->save(['touch' => false]);

                    $note->opportunitiesHaveNote()->sync($note->opportunitiesHaveNote);
                    $note->companiesHaveNote()->sync($note->companiesHaveNote);
                    $note->rescueQuotesHaveNote()->sync($note->rescueQuotesHaveNote);
                    $note->worldwideQuotesHaveNote()->sync($note->worldwideQuotesHaveNote);
                    $note->contactsHaveNote()->sync($note->contactsHaveNote);
                });
            });

            return $note;
        }

        $note = $this->dataMapper->mapFromNoteEntity($entity);

        $this->connection->transaction(static function () use ($note): void {
            $note->owner->save();

            $note->save();

            $note->opportunitiesHaveNote()->attach($note->opportunitiesHaveNote);
            $note->companiesHaveNote()->attach($note->companiesHaveNote);
            $note->rescueQuotesHaveNote()->attach($note->rescueQuotesHaveNote);
            $note->worldwideQuotesHaveNote()->attach($note->worldwideQuotesHaveNote);
            $note->contactsHaveNote()->attach($note->contactsHaveNote);
        });

        return $note;
    }

    public function syncByReference(string $reference): Model
    {
        return $this->sync(
            $this->noteIntegration->getById($reference)
        );
    }

    public function countPending(): int
    {
        [$count, $lastId] = $this->computeTotalEntitiesCountAndLastIdToPull();

        return $count;
    }

    public function iteratePending(): \Traversable
    {
        $latestCursor = $this->getMostRecentScrollCursor();

        return $this->noteIntegration->scroll(
            ...$this->resolveScrollParameters(),
        );
    }

    public function getModelType(): string
    {
        return (new Note())->getMorphClass();
    }

    private function computeTotalEntitiesCountAndLastIdToPull(): array
    {
        /** @var \Generator $iterator */
        $iterator = $this->noteIntegration->simpleScroll(
            ...$this->resolveScrollParameters(),
            ...['first' => 1_000]
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

    #[ArrayShape(['after' => 'string|null'])]
    private function resolveScrollParameters(): array
    {
        return [
            'after' => $this->getMostRecentScrollCursor()?->cursor,
        ];
    }

    private function getMostRecentScrollCursor(): ?PipelinerModelScrollCursor
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return PipelinerModelScrollCursor::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->first();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Note;
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class])]
    public function getMetadata(string $reference): array
    {
        $entity = $this->noteIntegration->getById($reference);

        return [
            'id' => $entity->id,
            'revision' => $entity->revision,
            'created' => $entity->created,
            'modified' => $entity->modified,
        ];
    }
}