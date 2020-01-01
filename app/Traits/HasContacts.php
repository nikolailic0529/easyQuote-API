<?php

namespace App\Traits;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasContacts
{
    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function hardwareContacts()
    {
        return $this->contacts()->type('Hardware');
    }

    public function softwareContacts()
    {
        return $this->contacts()->type('Software');
    }

    public function hardwareContact()
    {
        return $this->morphOne(Contact::class, 'contactable')->type('Hardware')->withDefault($this->hardwareContacts()->make([]));
    }

    public function softwareContact()
    {
        return $this->morphOne(Contact::class, 'contactable')->type('Software')->withDefault($this->softwareContacts()->make([]));
    }
}
