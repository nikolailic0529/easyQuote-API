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
        return $this->addresses->firstWhere('address_type', 'Equipment') ?? optional();
    }

    public function getHardwareAddressAttribute()
    {
        return $this->addresses->firstWhere('address_type', 'Hardware') ?? optional();
    }

    public function getSoftwareAddressAttribute()
    {
        return $this->addresses->firstWhere('address_type', 'Software') ?? optional();
    }
}
