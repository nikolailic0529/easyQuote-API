<?php

namespace App\Models;

use App\Models\UuidModel;
use App\Traits\HasUser;

class Role extends UuidModel
{
    use HasUser;
    
    public function scopeAdmin($query)
    {
        return $query->where('is_admin', true)->first();
    }
}
