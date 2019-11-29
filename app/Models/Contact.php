<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends UuidModel
{
    use SoftDeletes;

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
