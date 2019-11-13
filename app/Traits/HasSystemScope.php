<?php

namespace App\Traits;

trait HasSystemScope
{
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
