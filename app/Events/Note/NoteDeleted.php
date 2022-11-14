<?php

namespace App\Events\Note;

use App\Models\Note\Note;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

final class NoteDeleted
{
    use SerializesModels;

    public function __construct(
        public readonly Note $note,
        public readonly ?Model $causer,
    ) {
    }
}
