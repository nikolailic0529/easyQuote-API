<?php

namespace App\Traits;

use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuotes
{
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
