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
        return array_values(json_decode($this->attributes['group_description'], true) ?? []);
    }
}
