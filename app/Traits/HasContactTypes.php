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
        return $this->hardwareContacts->first(null, $this->contacts()->make([]));
    }

    public function getSoftwareContactAttribute()
    {
        return $this->softwareContacts->first(null, $this->contacts()->make([]));
    }
}
