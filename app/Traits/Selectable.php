<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Selectable
{
    public function markAsSelected(): bool
    {
        return $this->forceFill(['is_selected' => true])->save();
    }

    public function markAsUnSelected(): bool
    {
        return $this->forceFill(['is_selected' => false])->save();
    }

    public function isSelected(): bool
    {
        return (bool) $this->is_selected;
    }

    public function scopeSelected(Builder $query): Builder
    {
        return $query->where('is_selected', true);
    }

    public function scopeNotSelected(Builder $query): Builder
    {
        return $query->where('is_selected', false);
    }
}
