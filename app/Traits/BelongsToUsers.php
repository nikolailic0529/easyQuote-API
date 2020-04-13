<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait BelongsToUsers
{
    public array $syncedUsers = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
