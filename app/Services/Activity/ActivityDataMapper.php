<?php

namespace App\Services\Activity;

use App\Models\System\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ActivityDataMapper
{
    const OLD_ATTRS_KEY = ChangesDetector::OLD_ATTRS_KEY;

    const NEW_ATTRS_KEY = ChangesDetector::NEW_ATTRS_KEY;

    const EXCLUDE_ATTRS = ['id'];

    public function mapActivityLogPaginator(LengthAwarePaginatorContract $paginator): LengthAwarePaginatorContract
    {
        return tap($paginator, function (LengthAwarePaginatorContract $paginator) {

            $items = $paginator->items();

            $this->mapActivityLogEntities(...$items);
        });
    }

    public function mapActivityLogEntities(Activity ...$activities): void
    {
        foreach ($activities as $item) {
            $item->setAttribute('subject_name', $this->resolveSubjectNameOfActivity($item));
            $item->setAttribute('attribute_changes', $this->mapEntityChangesOfActivity($item));
        }
    }

    public function resolveSubjectNameOfActivity(Activity $activity): string
    {
        $model = Relation::getMorphedModel($activity->subject_type) ?? $activity->subject_type;

        $classBaseName = class_basename($model);

        return "$classBaseName ({$activity->subject_id})";
    }

    public function mapEntityChangesOfActivity(Activity $activity): array
    {
        $changes = $activity->properties ?? [];

        if (empty($changes) || (!isset($changes[self::OLD_ATTRS_KEY]) && !isset($changes[self::NEW_ATTRS_KEY]))) {
            return [
                self::OLD_ATTRS_KEY => [],
                self::NEW_ATTRS_KEY => []
            ];
        }

        $newAttributeKeys = array_keys($changes[self::NEW_ATTRS_KEY] ?? []);

        return [
            self::OLD_ATTRS_KEY => $this->mapAttributeValues(Arr::only($changes[self::OLD_ATTRS_KEY] ?? [], $newAttributeKeys)),
            self::NEW_ATTRS_KEY => $this->mapAttributeValues($changes[self::NEW_ATTRS_KEY] ?? [])
        ];
    }

    protected function mapAttributeValues(array $attributeValues): array
    {
        $values = [];

        foreach ($attributeValues as $key => $value) {
            $attributeName = static::resolveNameOfAttribute($key);

            $values[] = [
                'attribute' => $attributeName,
                'value' => static::resolveValueOfAttributeValue($value)
            ];
        }

        return $values;
    }

    public static function resolveValueOfAttributeValue($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        if (blank($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return nl2br((string)$value);
    }

    public static function resolveNameOfAttribute(string $attributeName): string
    {
        $attributeName = Str::before($attributeName, '.');

        if (!ctype_lower($attributeName)) {
            $attributeName = preg_replace('/\s+/u', '', ucwords($attributeName));
            $attributeName = Str::lower(preg_replace('/(.)(?=[A-Z])/u', '$1 ', $attributeName));
        }

        return ucwords(str_replace(['-', '_'], ' ', $attributeName));
    }
}
