<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Note\Models\Note;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use App\Domain\Shared\Ownership\Contracts\ProvidesLinkedModels;
use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\Shared\Ownership\Exceptions\UnsupportedModelException;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class WorldwideQuoteVersionOwnershipService implements ChangeOwnershipStrategy, ProvidesLinkedModels, CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly WorldwideQuoteDataMapper $mapper,
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly WorldwideQuoteVersionGuard $versionGuard,
    ) {
    }

    public function changeOwnership(Model $model, ChangeOwnershipData $data): void
    {
        if (!$model instanceof WorldwideQuoteVersion) {
            throw new UnsupportedModelException();
        }

        $model->user()->associate($data->ownerId);

        if ($model->isDirty()) {
            $model->user_version_sequence_number = $this->versionGuard->resolveNewVersionNumberForUser(
                worldwideQuote: $model->worldwideQuote,
                userId: $data->ownerId,
            );
        }

        $this->conResolver->connection()->transaction(static function () use ($model): void {
            $model->save();
        });
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }

    public function getLinkedModels(Model $model): iterable
    {
        if (!$model instanceof WorldwideQuote) {
            return;
        }

        yield from $model->opportunity->getRelationValue('notes')->lazy()->reject(static function (Note $note): bool {
            return $note->getFlag(Note::SYSTEM);
        });
        yield from $model->opportunity->tasks;
        yield from $model->opportunity->ownAppointments;
        yield from $model->opportunity->attachments;
    }
}
