<?php

namespace App\Services\WorldwideQuote;

use App\Enum\Lock;
use App\Events\WorldwideQuote\WorldwideQuoteNoteCreated;
use App\Events\WorldwideQuote\WorldwideQuoteNoteDeleted;
use App\Events\WorldwideQuote\WorldwideQuoteNoteUpdated;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Models\User;
use App\Queries\Exceptions\ValidationException;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Events\Dispatcher;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function tap;

class WorldwideQuoteNoteService
{
    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    protected ValidatorInterface $validator;

    protected Dispatcher $eventDispatcher;

    public function __construct(ConnectionInterface $connection, LockProvider $lockProvider, ValidatorInterface $validator, Dispatcher $eventDispatcher)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createWorldwideQuoteNote(string $noteText, WorldwideQuote $worldwideQuote, ?User $user = null): WorldwideQuoteNote
    {
        $violations = $this->validator->validate($noteText, [new NotBlank]);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new WorldwideQuoteNote, function (WorldwideQuoteNote $worldwideQuoteNote) use ($worldwideQuote, $user, $noteText) {
            $worldwideQuoteNote->text = $noteText;
            $worldwideQuoteNote->worldwideQuote()->associate($worldwideQuote);
            $worldwideQuoteNote->user()->associate($user);

            $this->connection->transaction(fn() => $worldwideQuoteNote->save());

            $this->eventDispatcher->dispatch(new WorldwideQuoteNoteCreated($worldwideQuoteNote));
        });
    }

    public function updateWorldwideQuoteNote(string $noteText, WorldwideQuoteNote $worldwideQuoteNote): WorldwideQuoteNote
    {
        $violations = $this->validator->validate($noteText, [new NotBlank]);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($worldwideQuoteNote, function (WorldwideQuoteNote $worldwideQuoteNote) use ($noteText) {
            $worldwideQuoteNote->text = $noteText;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE_NOTE($worldwideQuoteNote->getKey()),
                10
            );

            $lock->block(30, function () use ($worldwideQuoteNote) {

                $this->connection->transaction(fn() => $worldwideQuoteNote->save());

            });

            $this->eventDispatcher->dispatch(new WorldwideQuoteNoteUpdated($worldwideQuoteNote));
        });
    }

    public function deleteWorldwideQuoteNote(WorldwideQuoteNote $worldwideQuoteNote): void
    {
        $lock = $this->lockProvider->lock(
            Lock::DELETE_WWQUOTE_NOTE($worldwideQuoteNote->getKey()),
            10
        );

        $lock->block(30, function () use ($worldwideQuoteNote) {

            $worldwideQuoteNote->delete();

        });

        $this->eventDispatcher->dispatch(new WorldwideQuoteNoteDeleted($worldwideQuoteNote));
    }

}
