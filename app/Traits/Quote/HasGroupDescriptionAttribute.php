<?php namespace App\Traits\Quote;

use Arr;

trait HasGroupDescriptionAttribute
{
    public function initializeHasGroupDescriptionAttribute()
    {
        $this->casts = array_merge($this->casts, ['group_description' => 'array']);
    }

    public function getHasGroupDescriptionAttribute()
    {
        return !is_null($this->group_description);
    }

    public function getGroupDescriptionAttribute()
    {
        $group_description = json_decode($this->attributes['group_description'], true);
        return is_null($group_description) ? null : array_values($group_description);
    }
}
