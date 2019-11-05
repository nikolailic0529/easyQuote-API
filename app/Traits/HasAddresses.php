<?php namespace App\Traits;

use App\Models\Address;

trait HasAddresses
{
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function hardwareAddresses()
    {
        return $this->addresses()->where('address_type', 'Hardware');
    }

    public function equipmentAddresses()
    {
        return $this->addresses()->where('address_type', 'Equipment');
    }

    public function softwareAddresses()
    {
        return $this->addresses()->where('address_type', 'Software');
    }

    public function hardwareContacts()
    {
        return $this->contacts()->where('contact_type', 'Hardware');
    }

    public function softwareContacts()
    {
        return $this->contacts()->where('contact_type', 'Software');
    }

    public function getEquipmentAddressAttribute()
    {
        return $this->equipmentAddresses->first(null, $this->equipmentAddresses()->make([]));
    }

    public function getHardwareContactAttribute()
    {
        return $this->hardwareContacts->first(null, $this->hardwareContacts()->make([]));
    }

    public function getSoftwareAddressAttribute()
    {
        return $this->softwareAddresses->first(null, $this->softwareAddresses()->make([]));
    }

    public function getSoftwareContactAttribute()
    {
        return $this->softwareContacts->first(null, $this->softwareContacts()->make([]));
    }
}
