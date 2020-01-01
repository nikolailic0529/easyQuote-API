<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait BelongsToUsers
{
    public function user(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
