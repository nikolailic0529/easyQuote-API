<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Support\Collection;

trait BelongsToAddresses
{
    public function addresses()
    {
        return $this->morphToMany(Address::class, 'addressable');
    }

    public function syncAddresses(?array $addresses, bool $detach = true)
    {
        if (blank($addresses)) {
            return;
        }

        $oldAddresses = $this->addresses;

        $changes = $this->addresses()->sync($addresses, $detach);

        $this->logChangedAddresses($changes, $oldAddresses);
    }

    public function detachAddresses(?array $addresses)
    {
        if (blank($addresses)) {
            return;
        }

        $oldAddresses = $this->addresses;

        $changes = $this->addresses()->detach($addresses);

        $this->logChangedAddresses($changes, $oldAddresses);
    }

    protected function logChangedAddresses($changes, Collection $old)
    {
        if (!$changes || (is_array($changes) && blank(array_flatten($changes)))) {
            return;
        }

        activity()
            ->on($this)
            ->withAttribute('addresses', $this->load('addresses')->addresses->toString('address_1', 'address_type'), $old->toString('address_1', 'address_type'))
            ->log('updated');
    }
}
