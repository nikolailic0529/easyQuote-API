<?php

namespace App\Events\Address;

use App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AddressUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(protected Address $address,
                                protected Address $newAddress,
                                protected ?Model  $causer = null)
    {
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getNewAddress(): Address
    {
        return $this->newAddress;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
