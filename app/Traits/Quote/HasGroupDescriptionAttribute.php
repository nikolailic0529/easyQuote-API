<?php namespace App\Traits\Quote;

trait HasGroupDescriptionAttribute
{
    public function initializeHasGroupDescriptionAttribute()
    {
        $this->casts = array_merge($this->casts, ['group_description' => 'array']);
    }

    public function getHasGroupDescriptionAttribute()
    {
        return filled($this->group_description);
    }

    public function findGroupDescription(string $id)
    {
        return collect($this->group_description)->search(function ($group) use ($id) {
            return isset($group['id']) && $group['id'] === $id;
        });
    }
}
