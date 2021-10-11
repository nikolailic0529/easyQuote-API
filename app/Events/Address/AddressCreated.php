<?php

namespace App\Events\Address;

use App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AddressCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(protected Address $address,
                                protected ?Model  $causer = null)
    {
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
