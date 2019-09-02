<?php namespace App\Models;

use App\Models\UuidModel;
use App\Traits\BelongsToUsers;

class Role extends UuidModel
{
    use BelongsToUsers;
    
    public function scopeAdmin($query)
    {
        return $query->where('is_admin', true)->first();
    }
}
