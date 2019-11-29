<?php

namespace App\Traits;

use App\Models\Address;

trait HasAddresses
{
    public function addresses()
    {
        return $this->belongsToMany(Address::class);
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
        return $this->belongsTo(Address::class)->type('Equipment')->withDefault($this->hardwareAddresses()->make([]));
    }

    public function hardwareAddress()
    {
        return $this->belongsTo(Address::class)->type('Hardware')->withDefault($this->hardwareAddresses()->make([]));
    }

    public function softwareAddress()
    {
        return $this->belongsTo(Address::class)->type('Software')->withDefault($this->hardwareAddresses()->make([]));
    }
}
