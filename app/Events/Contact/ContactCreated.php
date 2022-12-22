<?php

namespace App\Events\Contact;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class ContactCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Contact $contact,
        public readonly ?Model $causer = null
    ) {
    }
}
