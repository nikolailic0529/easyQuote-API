<?php

namespace App\Events\RescueQuote;

use App\Contracts\HasOwnNotes;
use App\Models\Note\Note;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NoteCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Note              $note,
                                public readonly Model&HasOwnNotes $model)
    {
    }
}
