<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSystemScope
{
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }
}
