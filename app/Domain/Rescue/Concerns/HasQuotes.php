<?php

namespace App\Domain\Rescue\Concerns;

use App\Domain\Rescue\Models\Quote;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuotes
{
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
