<?php namespace App\Traits\Quote;

use Arr;

trait HasGroupDescriptionAttribute
{
    public function initializeHasGroupDescriptionAttribute()
    {
        $this->fillable = array_merge($this->fillable, ['group_description']);
        $this->casts = array_merge($this->casts, ['group_description' => 'array']);
    }

    public function setGroupDescriptionAttribute(array $value)
    {
        $groups = json_encode(array_map(function ($group) {
            return Arr::only($group, ['name', 'search_text']);
        }, $value));

        if($this->attributes['group_description'] === $groups) {
            return;
        }

        $this->attributes['group_description'] = $groups;

        $this->createGroupDescription($value);
    }

    public function createGroupDescription(array $value)
    {
        $this->rowsData()->whereNotNull('group_name')->update(['group_name' => null]);

        foreach ($value as $group) {
            $this->rowsData()->whereIn('imported_rows.id', $group['rows'])->update(['group_name' => $group['name']]);
        }
    }
}
