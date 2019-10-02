<?php namespace App\Models;

class Address extends UuidModel
{
    public function addressable()
    {
        return $this->morphTo();
    }
}
