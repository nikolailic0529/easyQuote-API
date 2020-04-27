<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasUsers
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}