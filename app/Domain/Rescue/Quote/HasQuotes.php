<?php

namespace App\Domain\Rescue\Quote;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuotes
{
    public function quotes(): HasMany
    {
        return $this->hasMany(\App\Domain\Rescue\Models\Quote::class);
    }
}
