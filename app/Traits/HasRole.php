<?php

namespace App\Traits;

use App\Models\Role;

trait HasRole
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}