<?php

namespace App\Models\System;

use App\Models\User;
use App\Traits\{
    Uuid,
    Search\Searchable,
};
use Illuminate\Support\{
    Arr,
    Collection
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Relations\MorphTo
};
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Illuminate\Support\Str;

class Activity extends Model implements ActivityContract
{
    use Uuid, Searchable;

    public $guarded = [];

    protected $casts = [
        'properties' => 'collection',
    ];

    protected $hidden = [
        'properties'
    ];

    protected $dateTimeFormat = 'm/d/y, h:i A';

    public function __construct(array $attributes = [])
    {
        if (!isset($this->connection)) {
            $this->setConnection(config('activitylog.database_connection'));
        }

        if (!isset($this->table)) {
            $this->setTable(config('activitylog.table_name'));
        }

        parent::__construct($attributes);
    }

    public function subject(): MorphTo
    {
        if (config('activitylog.subject_returns_soft_deleted_models')) {
            return $this->morphTo()->withTrashed();
        }

        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getExtraProperty(string $propertyName)
    {
        return Arr::get($this->properties->toArray(), $propertyName);
    }

    public function changes(): Collection
    {
        if (!$this->properties instanceof Collection) {
            return new Collection();
        }

        return $this->properties->only(['attributes', 'old']);
    }

    public function getChangesAttribute(): Collection
    {
        return $this->changes();
    }

    public function scopeInLog(Builder $query, ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeForSubjectId(Builder $query, string $subject_id): Builder
    {
        return $query->where('subject_id', $subject_id);
    }

    public function scopeHasCauser(Builder $query): Builder
    {
        return $query->whereHasMorph('causer', User::class);
    }

    public function toSearchArray()
    {
        return [
            'log_name'           => $this->log_name,
            'description'        => $this->description,
            'changed_properties' => $this->readableChanges->collapse()->pluck('attribute')->unique()->toArray(),
            'causer_name'        => $this->causer_name,
            'subject_name'       => $this->subject_name,
            'created_at'         => optional($this->created_at)->format(config('date.format')),
        ];
    }

    public function getReadableChangesAttribute()
    {
        $expectedChanges = collect(['old' => [], 'attributes' => []]);

        if ($this->changes()->isEmpty()) {
            return $expectedChanges;
        }

        return $this->changes()->map(function ($change) {
            return collect($change)->except('id')->map(function ($value, $key) {
                $attribute = Str::formatAttributeKey($key);

                if (is_iterable($value)) {
                    $value = collect($value)->flatten()->implode(', ');
                }

                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                }

                $value = blank($value) ? null : (string) $value;

                return compact('attribute', 'value');
            })->values();
        })->union($expectedChanges);
    }

    public function getDescriptionAttribute($value)
    {
        return isset($value)
            ? Str::spaced(Str::studly($value))
            : null;
    }

    public function getCauserNameAttribute()
    {
        if (isset($this->causer_service)) {
            return $this->causer_service;
        }

        if (isset($this->causer)) {
            return "{$this->causer->email} ({$this->causer->full_name})";
        }

        return null;
    }

    public function getSubjectTypeBaseAttribute(): string
    {
        return Str::spaced(class_basename($this->subject_type));
    }

    public function getSubjectNameAttribute(): string
    {
        return $this->subject->item_name ?? "{$this->subject_type_base} ({$this->subject_id})";
    }
}
