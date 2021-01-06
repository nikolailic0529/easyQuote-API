<?php

namespace App\Traits\Quote;

use Illuminate\Support\Collection;

trait HasGroupDescriptionAttribute
{
    public function initializeHasGroupDescriptionAttribute()
    {
        $this->casts = array_merge($this->casts, ['use_groups' => 'boolean', 'sort_group_description' => 'array']);
        $this->fillable = array_merge($this->fillable, ['group_description', 'use_groups', 'sort_group_description']);
        $this->appends = array_merge($this->appends, ['has_group_description']);
    }

    public function getHasGroupDescriptionAttribute(): bool
    {
        return Collection::wrap($this->group_description)->isNotEmpty();
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

        $this->attributes['use_groups'] = $value;
    }

    public function groupsReady(): bool
    {
        return $this->use_groups && $this->has_group_description;
    }

    public function groupedRows(): array
    {
        return $this->group_description->pluck('rows_ids')->flatten(1)->toArray();
    }

    public function getSelectedGroupDescriptionNamesAttribute(): array
    {
        return Collection::wrap($this->group_description)->where('is_selected', true)->pluck('name')->toArray();
    }
}
