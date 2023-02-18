<?php

namespace App\Domain\Note\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\DataTransferObjects\CreateNoteData;
use App\Domain\Note\DataTransferObjects\UpdateNoteData;
use App\Domain\Note\Events\NoteCreated;
use App\Domain\Note\Events\NoteDeleted;
use App\Domain\Note\Events\NoteUpdated;
use App\Domain\Note\Models\Note;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class NoteEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected EventDispatcher $eventDispatcher
    ) {
    }

    public function createNoteForModel(CreateNoteData $data, Model&HasOwnNotes $model): Note
    {
        return tap(new Note(), function (Note $note) use ($data, $model): void {
            $note->owner()->associate($this->causer);
            $note->note = $data->note;

            if ($data->flags !== null) {
                $note->flags = $data->flags;
            }

            $this->connection->transaction(function () use ($note, $model): void {
                $note->save();
                $model->notes()->attach($note);
                $this->touchRelated($note);
            });

            $this->eventDispatcher->dispatch(
                new NoteCreated($note, $model, $this->causer),
            );
        });
    }

    public function updateNote(Note $note, UpdateNoteData $data): Note
    {
        return tap($note, function (Note $note) use ($data): void {
            $old = (new Note())->setRawAttributes($note->getRawOriginal());

            $note->note = $data->note;

            $this->connection->transaction(function () use ($note) {
                $note->save();
                $this->touchRelated($note);
            });

            $this->eventDispatcher->dispatch(
                new NoteUpdated($old, $note, $this->causer)
            );
        });
    }

    public function deleteNote(Note $note): void
    {
        $this->connection->transaction(function () use ($note) {
            $note->delete();
            $this->touchRelated($note);
        });

        $this->eventDispatcher->dispatch(
            new NoteDeleted($note, $this->causer)
        );
    }

    protected function touchRelated(Note $note): void
    {
        foreach ($note->modelsHaveNote as $model) {
            /* @var $model \App\Domain\Note\Models\ModelHasNotes */
            $model->related?->touch();
        }
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }
}
