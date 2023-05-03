<?php

namespace App\Domain\Contact\Events;

use App\Domain\Contact\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class ContactUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly Contact $contact,
        public readonly Contact $newContact,
        public readonly ?Model $causer = null
    ) {
    }
}
