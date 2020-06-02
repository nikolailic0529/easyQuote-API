<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait BelongsToAddresses
{
    public function addresses(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'addressable');
    }

    public function syncAddresses(?array $addresses, bool $detach = true): void
    {
        $addresses ??= [];

        $oldAddresses = $this->addresses;

        $changes = $this->addresses()->sync($addresses, $detach);

        $this->logChangedAddresses($changes, $oldAddresses);
    }

    public function detachAddresses(?array $addresses): void
    {
        if (blank($addresses)) {
            return;
        }

        $oldAddresses = $this->addresses;

        $changes = $this->addresses()->detach($addresses);

        $this->logChangedAddresses($changes, $oldAddresses);
    }

    protected function logChangedAddresses($changes, Collection $old): void
    {
        if (!$changes || (is_array($changes) && blank(array_flatten($changes)))) {
            return;
        }

        activity()
            ->on($this)
            ->withAttribute('addresses', $this->load('addresses')->addresses->toString('address_1', 'address_type'), $old->toString('address_1', 'address_type'))
            ->queue('updated');
    }
}
