<?php

namespace App\Domain\Activity\Models;

use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

/**
 * @property Collection|null $properties
 * @property array|null      $attribute_changes
 * @property string|null     $subject_type
 * @property string|null     $subject_id
 * @property string|null     $causer_service
 * @property string|null     $description
 */
class Activity extends Model implements ActivityContract, SearchableEntity
{
    use Uuid;
    use Searchable;

    public bool $submitEmptyLogs = true;

    protected $guarded = [];

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
        $subjectType = Relation::getMorphedModel($this->subject_type) ?? $this->subject_type;

        if (class_exists($subjectType)) {
            $subjectType = class_basename($subjectType);
        }

        $subjectType = strtolower($subjectType);

        return [
            'log_name' => $this->log_name,
            'description' => $this->description,
            'old_properties' => collect($this->changes()->get('old', []))
                ->filter(static fn (mixed $v): bool => filled($v))
                ->mapWithKeys(static function (mixed $v, string $k) use ($subjectType): array {
                    return ["{$subjectType}_$k" => (string) $v];
                })
                ->all(),
            'attributes' => collect($this->changes()->get('attributes', []))
                ->filter(static fn (mixed $v): bool => filled($v))
                ->mapWithKeys(static function (mixed $v, string $k) use ($subjectType): array {
                    return ["{$subjectType}_$k" => (string) $v];
                })
                ->all(),
            'causer_name' => $this->causer_name,
            'subject_id' => $this->subject()->getParentKey(),
            'subject_type' => $subjectType,
            'subject_name' => $this->subject_name,
            'created_at' => optional($this->created_at)->format(config('date.format')),
        ];
    }
}
