<?php

namespace App\Traits;

use App\Models\Contact;

trait HasContacts
{
    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }
}
