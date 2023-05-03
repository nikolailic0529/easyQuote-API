<?php

namespace App\Domain\Note\Events;

use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\Note;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

final class NoteCreated
{
    use SerializesModels;

    public function __construct(
        public readonly Note $note,
        public readonly Model&HasOwnNotes $model,
        public readonly ?Model $causer,
    ) {
    }
}
