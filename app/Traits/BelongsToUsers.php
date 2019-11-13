<?php

namespace App\Traits;

use App\Models\User;

trait BelongsToUsers
{
    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
