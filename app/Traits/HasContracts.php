<?php

namespace App\Traits;

use App\Models\Quote\Contract;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasContracts
{
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}