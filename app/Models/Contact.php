<?php

namespace App\Models;

class Contact extends UuidModel
{
    protected $fillable = [
        'contact_type'
    ];

    public function contactable()
    {
        return $this->morphTo();
    }
}
