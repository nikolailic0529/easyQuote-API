<?php

namespace App\Domain\Settings\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Settings\Casts\ConditionalCast;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App\Domain\Settings\Models\SystemSetting.
 *
 * @property string                                            $key
 * @property string                                            $type
 * @property bool                                              $is_read_only
 * @property string                                            $id
 * @property string                                            $section
 * @property mixed|null                                        $value
 * @property int                                               $order
 * @property mixed|null                                        $possible_values
 * @property array|null                                        $validation
 * @property string|null                                       $label_format
 * @property string                                            $field_type
 * @property Collection|\App\Domain\Activity\Models\Activity[] $activities
 * @property int|null                                          $activities_count
 * @property mixed                                             $field_title
 * @property string                                            $item_name
 * @property mixed                                             $label
 * @property mixed                                             $log_value
 * @property string                                            $value_cache_key
 *
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereIsReadOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereLabelFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting wherePossibleValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereSection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereValidation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemSetting whereValue($value)
 */
class SystemSetting extends Model
{
    use Uuid;
    use LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'possible_values', 'value', 'type', 'key', 'section', 'order', 'validation',
    ];

    protected $hidden = [
        'label_format', 'type', 'key',
    ];

    protected $casts = [
        'value' => ConditionalCast::class,
        'validation' => 'array',
        'is_read_only' => 'boolean',
    ];

    protected $appends = [
        'label', 'field_title', 'field_type',
    ];

    public static $cachedValues = [];

    protected static $logAttributes = [
        'value:log_value',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $recordEvents = ['updated'];

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
            return __($this->label_format, ['value' => $this->valueToString()]);
        }

        if (blank($this->possible_values)) {
            return $this->valueToString();
        }

        $selected = collect($this->possible_values)->firstWhere('value', '===', $this->value);

        return data_get($selected, 'label');
    }

    public function getFieldTitleAttribute()
    {
        return __('settings.titles.'.$this->getRawOriginal('key'));
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
