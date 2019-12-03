<?php

namespace App\Traits;

use App\Models\Contact;
use Illuminate\Support\Collection;

trait BelongsToContacts
{
    public function contacts()
    {
        return $this->morphToMany(Contact::class, 'contactable');
    }

    public function syncContacts(?array $contacts, bool $detach = true)
    {
        if (blank($contacts)) {
            return;
        }

        $oldContacts = $this->contacts;

        $changes = $this->contacts()->sync($contacts, $detach);

        $this->logChangedContacts($changes, $oldContacts);
    }

    public function detachContacts(?array $contacts)
    {
        if (blank($contacts)) {
            return;
        }

        $oldContacts = $this->contacts;

        $changes = $this->contacts()->detach($contacts);

        $this->logChangedContacts($changes, $oldContacts);
    }

    protected function logChangedContacts($changes, Collection $old)
    {
        if (!$changes || (is_array($changes) && blank(array_flatten($changes)))) {
            return;
        }

        activity()
            ->on($this)
            ->withAttribute('contacts', $this->load('contacts')->contacts->toString('item_name'), $old->toString('item_name'))
            ->log('updated');
    }
}
