<?php

namespace App\Models\System;

use App\Models\UuidModel;
use Spatie\Activitylog\Traits\LogsActivity;
use Str;

class SystemSetting extends UuidModel
{
    use LogsActivity;

    public $timestamps = false;

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

    protected static $logAttributes = [
        'value'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $recordEvents = ['updated'];

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
            return $this->value->format(config('date.format_with_time'));
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

    public function getValueCacheKeyAttribute()
    {
        return "setting-value:{$this->key}";
    }

    public function forgetCachedValue()
    {
        return cache()->forget($this->valueCacheKey);
    }

    public function getItemNameAttribute(): string
    {
        $key = Str::formatAttributeKey($this->key);

        return "Setting ({$key})";
    }
}
