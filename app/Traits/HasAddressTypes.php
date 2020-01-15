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
        return optional(
            $this->addresses->firstWhere('address_type', 'Equipment')
        );
    }

    public function getHardwareAddressAttribute()
    {
        return optional(
            $this->addresses->firstWhere('address_type', 'Hardware')
        );
    }

    public function getSoftwareAddressAttribute()
    {
        return optional(
            $this->addresses->firstWhere('address_type', 'Software')
        );
    }
}
