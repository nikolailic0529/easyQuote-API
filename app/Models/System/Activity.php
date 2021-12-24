<?php

namespace App\Models\System;

use App\Contracts\SearchableEntity;
use App\Traits\{Search\Searchable, Uuid,};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\MorphTo};
use Illuminate\Support\{Arr, Collection};
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

/**
 * @property-read Collection|null $properties
 * @property-read array|null $attribute_changes
 * @property string|null $subject_type
 * @property string|null $subject_id
 */
class Activity extends Model implements ActivityContract, SearchableEntity
{
    use Uuid, Searchable;

    public $guarded = [];

    protected $casts = [
        'properties' => 'collection',
    ];

    protected $hidden = [
        'properties',
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
        return Arr::get($this->properties, $propertyName);
    }

    public function changes(): Collection
    {
        $properties = Collection::wrap($this->properties);

        return $properties->only(['attributes', 'old']);
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

    public function toSearchArray(): array
    {
        return [
            'log_name' => $this->log_name,
            'description' => $this->description,
            'changed_properties' => $this->changes()->all(),
            'causer_name' => $this->causer_name,
            'subject_name' => $this->subject_name,
            'created_at' => optional($this->created_at)->format(config('date.format')),
        ];
    }
}
