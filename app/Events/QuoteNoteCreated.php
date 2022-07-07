<?php

namespace App\Events;

use App\Models\Note\Note;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QuoteNoteCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Note $note)
    {
    }
}
