<?php

namespace App\Services\Note;

use App\Contracts\CauserAware;
use App\Contracts\HasOwnNotes;
use App\DTO\Note\CreateNoteData;
use App\DTO\Note\UpdateNoteData;
use App\Events\RescueQuote\NoteCreated;
use App\Models\Note\Note;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class NoteEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected ConnectionInterface $connection,
                                protected EventDispatcher     $eventDispatcher)
    {
    }

    public function createNoteForModel(CreateNoteData $data, Model&HasOwnNotes $model): Note
    {
        return tap(new Note(), function (Note $note) use ($data, $model): void {
            $note->owner()->associate($this->causer);
            $note->note = $data->note;

            $this->connection->transaction(static function () use ($note, $model): void {
                $note->save();
                $model->notes()->attach($note);
            });

            $this->eventDispatcher->dispatch(
                new NoteCreated($note, $model),
            );
        });
    }

    public function updateNote(Note $note, UpdateNoteData $data): Note
    {
        return tap($note, function (Note $note) use ($data): void {
            $note->note = $data->note;

            $this->connection->transaction(static fn () => $note->save());
        });
    }

    public function deleteNote(Note $note): void
    {
        $this->connection->transaction(static fn () => $note->delete());
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }
}