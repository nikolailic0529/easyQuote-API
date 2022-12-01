<?php

namespace App\Services\Note;

use App\Contracts\CauserAware;
use App\Contracts\HasOwnNotes;
use App\DTO\Note\CreateNoteData;
use App\DTO\Note\UpdateNoteData;
use App\Events\Note\NoteCreated;
use App\Events\Note\NoteDeleted;
use App\Events\Note\NoteUpdated;
use App\Models\Note\ModelHasNotes;
use App\Models\Note\Note;
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
            $old = (new Note)->setRawAttributes($note->getRawOriginal());

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
            /** @var $model ModelHasNotes */
            $model->related?->touch();
        }
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }
}