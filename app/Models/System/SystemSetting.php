<?php

namespace App\Models\System;

use App\Casts\ConditionalCast;
use App\Traits\{Activity\LogsActivity, Uuid};
use Illuminate\Database\Eloquent\{Collection, Model,};
use Illuminate\Support\Str;

class SystemSetting extends Model
{
    use Uuid, LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'possible_values', 'value', 'type', 'key', 'section', 'order', 'validation'
    ];

    protected $hidden = [
        'label_format', 'type', 'key'
    ];

    protected $casts = [
        'value' => ConditionalCast::class,
        'possible_values' => 'array',
        'validation' => 'array',
        'is_read_only' => 'boolean'
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

    public function getPossibleValuesAttribute($value)
    {
        if (!is_array($value)) {
            $value = json_decode($value, true);
        }

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
        return __('setting.titles.'.$this->getRawOriginal('key'));
    }

    public function getFieldTypeAttribute(): string
    {
        if ($this->is_read_only) {
            return 'label';
        } elseif ($this->possible_values instanceof Collection) {
            return 'multiselect';
        } elseif (is_iterable($this->possible_values)) {
            return 'dropdown';
        } elseif ($this->type === 'boolean') {
            return 'checkbox';
        }

        return 'textbox';
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
