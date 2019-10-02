<?php namespace App\Traits;

use App\Models\Address;

trait HasAddresses
{
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
