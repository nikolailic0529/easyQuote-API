<?php

namespace App\Events\Contact;

use App\Models\Contact;
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
