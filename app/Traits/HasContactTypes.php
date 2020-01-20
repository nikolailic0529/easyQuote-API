<?php

namespace App\Traits;

trait HasContactTypes
{
    public function hardwareContacts()
    {
        return $this->contacts()->type('Hardware');
    }

    public function softwareContacts()
    {
        return $this->contacts()->type('Software');
    }

    public function getHardwareContactAttribute()
    {
        return $this->addresses->firstWhere('address_type', 'Hardware') ?? optional();
    }

    public function getSoftwareContactAttribute()
    {
        return $this->addresses->firstWhere('address_type', 'Software') ?? optional();
    }
}
