<?php namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Systemable
{
    public function isSystem()
    {
        return (bool) $this->getAttribute('is_system');
    }

    public function scopeSystem(Builder $query)
    {
        return $query->where('is_system', true);
    }
}
