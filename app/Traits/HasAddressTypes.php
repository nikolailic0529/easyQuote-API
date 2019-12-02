<?php

namespace App\Traits;

trait HasAddressTypes
{
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

    public function getEquipmentAddressAttribute()
    {
        return $this->equipmentAddresses->first(null, $this->addresses()->make([]));
    }

    public function getHardwareAddressAttribute()
    {
        return $this->hardwareAddresses->first(null, $this->addresses()->make([]));
    }

    public function getSoftwareAddressAttribute()
    {
        return $this->softwareAddresses->first(null, $this->addresses()->make([]));
    }
}
