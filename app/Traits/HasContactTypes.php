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
        return optional(
            $this->addresses->firstWhere('address_type', 'Hardware')
        );
    }

    public function getSoftwareContactAttribute()
    {
        return optional(
            $this->addresses->firstWhere('address_type', 'Software')
        );
    }
}
