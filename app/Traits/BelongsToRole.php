<?php

namespace App\Traits;

use App\Models\Role;

trait BelongsToRole
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
