<?php

namespace App\Models\System;

use App\Models\BaseModel;
use App\Traits\{
    Activity\LogsActivity,
    HasValidation
};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class SystemSetting extends BaseModel
{
    use LogsActivity, HasValidation, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'possible_values', 'value', 'type', 'key', 'section', 'order'
    ];

    protected $hidden = [
        'label_format', 'type', 'key', 'deleted_at'
    ];

    protected $casts = [
        'value' => '',
        'possible_values' => 'array',
        'is_read_only' => 'boolean'
    ];

    protected $types = [
        'string', 'integer', 'float', 'decimal', 'array', 'datetime'
    ];

    protected $appends = [
        'label', 'field_title', 'field_type'
    ];

    public static $cachedValues = [];

    protected static $logAttributes = [
        'value:log_value'
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

    public function setTypeAttribute($value): void
    {
        if (isset(array_flip($this->types)[$value])) {
            $this->attributes['type'] = $value;
            return;
        }

        $this->attributes['type'] = head($this->types);
    }

    public function setPossibleValuesAttribute($value): void
    {
        $this->attributes['possible_values'] = isset($value) ? json_encode($value) : null;
    }

    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getValueAttribute($value)
    {
        return $this->castAttribute('value', $value);
    }

    public function getPossibleValuesAttribute($value)
    {
        $value = json_decode($value, true);

        if (is_string($value)) {
            if (isset(static::$cachedValues[$value])) {
                return static::$cachedValues[$value];
            }

            $model = app(Str::before($value, ':'));
            $columns = Str::contains($value, ':') ? explode(',', Str::after($value, ':')) : ['*'];

            return static::$cachedValues[$value] = $model->get($columns);
        }

        return $value;
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
            return $this->value->format(config('date.format_time'));
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

    public function getFieldTypeAttribute(): string
    {
        if ($this->is_read_only) {
            return 'label';
        }

        if ($this->possible_values instanceof Collection) {
            return 'multiselect';
        }

        return is_iterable($this->possible_values) ? 'dropdown' : 'textbox';
    }

    public function getValueCacheKeyAttribute(): string
    {
        return "setting-value:{$this->key}";
    }

    public function forgetCachedValue(): bool
    {
        return cache()->forget($this->valueCacheKey);
    }

    public function cacheValue(): bool
    {
        return cache()->forever($this->valueCacheKey, $this->value);
    }

    public function getItemNameAttribute(): string
    {
        $key = Str::formatAttributeKey($this->key);

        return "Setting ({$key})";
    }

    public function getLogValueAttribute()
    {
        if ($this->possible_values instanceof Collection) {
            return $this->possible_values->whereIn('value', $this->value)->toString('label');
        }

        return $this->valueToString();
    }

    public function isKey(string $key): bool
    {
        return $this->getAttributeFromArray('key') === $key;
    }
}
