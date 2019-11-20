<?php

namespace App\Traits;

use App\Models\Address;

trait HasAddresses
{
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function hardwareAddresses()
    {
        return $this->addresses()->type('Hardware');
    }

    public function equipmentAddresses()
    {
        return $this->addresses()->type('Equipment');
    }

    public function softwareAddresses()
    {
        return $this->addresses()->type('Software');
    }

    public function equipmentAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->type('Equipment')->withDefault($this->hardwareAddresses()->make([]));
    }

    public function hardwareAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->type('Hardware')->withDefault($this->hardwareAddresses()->make([]));
    }

    public function softwareAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->type('Software')->withDefault($this->hardwareAddresses()->make([]));
    }
}
