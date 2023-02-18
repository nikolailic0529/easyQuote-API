<?php

namespace App\Domain\User\Concerns;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasUsers
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
