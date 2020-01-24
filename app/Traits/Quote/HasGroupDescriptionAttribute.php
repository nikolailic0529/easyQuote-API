<?php

namespace App\Traits\Quote;

trait HasGroupDescriptionAttribute
{
    public function initializeHasGroupDescriptionAttribute()
    {
        $this->casts = array_merge($this->casts, ['group_description' => 'array', 'use_groups' => 'boolean', 'sort_group_description' => 'array']);
        $this->fillable = array_merge($this->fillable, ['use_groups', 'sort_group_description']);
        $this->appends = array_merge($this->appends, ['has_group_description']);
    }

    public function getHasGroupDescriptionAttribute(): bool
    {
        return filled($this->group_description);
    }

    public function getHasNotGroupDescriptionAttribute(): bool
    {
        return !$this->has_group_description;
    }

    public function findGroupDescription(string $id)
    {
        return collect($this->group_description)->firstWhere('id', $id);
    }

    public function findGroupDescriptionKey(string $id)
    {
        return collect($this->group_description)->search(function ($group) use ($id) {
            return isset($group['id']) && $group['id'] === $id;
        });
    }

    public function resetGroupDescription(): bool
    {
        return $this->forceFill([
            'group_description' => null,
            'sort_group_description' => null
        ])->save();
    }

    public function setUseGroupsAttribute($value): void
    {
        $value = (bool) $value;

        if ((bool) $this->use_groups !== $value) {
            $this->forgetCachedComputableRows();
        }

        $this->attributes['use_groups'] = $value;
    }
}
