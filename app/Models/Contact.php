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

    public function scopeType($query, string $type)
    {
        return $query->whereContactType($type);
    }
}
