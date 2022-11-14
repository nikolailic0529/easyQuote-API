<?php

namespace App\Events\Note;

use App\Contracts\HasOwnNotes;
use App\Models\Note\Note;
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
