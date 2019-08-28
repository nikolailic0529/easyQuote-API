<?php

namespace App\Models;

use App\Models\UuidModel;

class Role extends UuidModel
{
    public function scopeAdmin($query)
    {
        return $query->where('is_admin', true)->first();
    }
}
