<?php

namespace App\Traits;

use App\Models\Data\Language;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait BelongsToLanguages
{
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }
}
