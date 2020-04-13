<?php

namespace App\Traits\Quote;

use Illuminate\Support\Collection;

trait HasGroupDescriptionAttribute
{
    public function initializeHasGroupDescriptionAttribute()
    {
        $this->casts = array_merge($this->casts, ['group_description' => 'array', 'use_groups' => 'boolean', 'sort_group_description' => 'array']);
        $this->fillable = array_merge($this->fillable, ['group_description', 'use_groups', 'sort_group_description']);
        $this->appends = array_merge($this->appends, ['has_group_description']);
    }

    public function setGroupDescriptionAttribute($value): void
    {
        if (is_string($value)) {
            $this->attributes['group_description'] = $value;
            return;
        }

        if (is_iterable($value)) {
            $this->attributes['group_description'] = json_encode($value, true);
            return;
        }

        $this->attributes['group_description'] = $value;
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
        return collect($this->group_description)->search(fn ($group) => isset($group['id']) && $group['id'] === $id);
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

    public function groupsReady(): bool
    {
        return $this->use_groups && $this->has_group_description;
    }

    public function getSelectedGroupDescriptionNamesAttribute(): array
    {
        return Collection::wrap($this->group_description)->where('is_selected', true)->pluck('name')->toArray();
    }
}
