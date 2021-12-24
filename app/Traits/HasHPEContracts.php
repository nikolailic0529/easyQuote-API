<?php

namespace App\Traits;

use App\Models\HpeContract;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasHPEContracts
{
    public function contracts(): HasMany
    {
        return $this->hasMany(HpeContract::class);
    }
}