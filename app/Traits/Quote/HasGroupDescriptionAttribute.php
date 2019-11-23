<?php

namespace App\Traits\Quote;

trait HasGroupDescriptionAttribute
{
    public function initializeHasGroupDescriptionAttribute()
    {
        $this->casts = array_merge($this->casts, ['group_description' => 'array', 'use_groups' => 'boolean']);
        $this->fillable = array_merge($this->fillable, ['use_groups']);
    }

    public function getHasGroupDescriptionAttribute()
    {
        return filled($this->group_description);
    }

    public function getHasNotGroupDescriptionAttribute()
    {
        return !$this->has_group_description;
    }

    public function findGroupDescription(string $id)
    {
        return collect($this->group_description)->search(function ($group) use ($id) {
            return isset($group['id']) && $group['id'] === $id;
        });
    }

    public function resetGroupDescription()
    {
        return $this->forceFill([
            'group_description' => null
        ])->save();
    }

    public function setUseGroupsAttribute($value)
    {
        $value = (bool) $value;

        $this->promiseRecalculateMargin();

        if ((bool) $this->use_groups !== $value) {
            $this->forgetCachedComputableRows();
        }

        $this->attributes['use_groups'] = $value;
    }
}
