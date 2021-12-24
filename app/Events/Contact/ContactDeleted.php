<?php

namespace App\Events\Contact;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ContactDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(protected Contact $contact,
                                protected ?Model  $causer = null)
    {
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
