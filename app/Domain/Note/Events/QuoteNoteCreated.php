<?php

namespace App\Domain\Note\Events;

use App\Domain\Note\Models\Note;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QuoteNoteCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Note $note)
    {
    }
}
