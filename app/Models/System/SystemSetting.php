<?php

namespace App\Models\System;

use App\Models\UuidModel;

class SystemSetting extends UuidModel
{
    protected $fillable = [
        'value', 'type'
    ];

    protected $hidden = [
        'label_format', 'type', 'key'
    ];

    protected $casts = [
        'value' => '',
        'possible_values' => 'array',
        'is_read_only' => 'boolean'
    ];

    protected $types = [
        'string', 'integer', 'array', 'datetime'
    ];

    protected $appends = [
        'label', 'field_title', 'field_type'
    ];

    protected $dateTimeFormat = 'd/m/Y';

    public $timestamps = false;

    protected function getCastType($key)
    {
        if ($key === 'value') {
            return $this->type;
        }

        return parent::getCastType($key);
    }

    public function setTypeAttribute($value)
    {
        if (in_array($value, $this->types)) {
            return $this->attributes['type'] = $value;
        }

        return $this->attributes['type'] = head($this->types);
    }

    public function getFlattenPossibleValuesAttribute()
    {
        return collect($this->possible_values)->pluck('value')->toArray();
    }

    public function valueToString()
    {
        if (is_array($this->value)) {
            return implode(', ', $this->value);
        }

        if ($this->value instanceof \Carbon\Carbon) {
            return $this->value->format($this->dateTimeFormat);
        }

        return $this->value;
    }

    public function getLabelAttribute()
    {
        if (filled($this->label_format)) {
            return str_replace(':value', $this->valueToString(), $this->label_format);
        }

        if (blank($this->possible_values)) {
            return $this->valueToString();
        }

        $selected = collect($this->possible_values)->firstWhere('value', '===', $this->value);

        return data_get($selected, 'label');
    }

    public function getFieldTitleAttribute()
    {
        return __("setting.titles.{$this->attributes['key']}");
    }

    public function getFieldTypeAttribute()
    {
        if ($this->is_read_only) {
            return 'label';
        }

        return is_array($this->possible_values) ? 'dropdown' : 'textbox';
    }
}
