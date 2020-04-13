<?php

namespace App\Models\Data;

use App\Contracts\HasOrderedScope;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class Language extends Model implements HasOrderedScope
{
    use Uuid;

    protected $hidden = [
        'pivot', 'native_name', 'code'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }
}
