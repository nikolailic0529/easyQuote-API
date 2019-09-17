<?php namespace App\Traits;

trait Selectable
{
    public function markAsSelected()
    {
        return $this->forceFill([
            'is_selected' => true,
        ])->save();
    }

    public function markAsUnSelected()
    {
        return $this->forceFill([
            'is_selected' => false,
        ])->save();
    }

    public function isSelected()
    {
        return (boolean) $this->is_selected;
    }

    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    public function scopeNotSelected($query)
    {
        return $query->where('is_selected', false);
    }
}
