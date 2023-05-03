<?php

namespace App\Domain\Rescue\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SchemalessColumns implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param mixed                               $value
     * @param array                               $attributes
     *
     * @return Collection
     */
    public function get($model, $key, $value, $attributes)
    {
        return collect(json_decode($value));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param array                               $value
     * @param array                               $attributes
     *
     * @return string|null
     */
    public function set($model, $key, $value, $attributes)
    {
        if (is_string($value)) {
            return static::handleStorableValue(collect(json_decode($value, true)));
        }

        if (is_iterable($value)) {
            return static::handleStorableValue(Collection::wrap($value));
        }
    }

    /**
     * Handle storable schemaless columns.
     *
     * @throws \InvalidArgumentException
     */
    protected static function handleStorableValue(Collection $value): string
    {
        if ($value->contains(fn ($column) => !Str::isUuid(data_get($column, 'importable_column_id')))) {
            throw new \InvalidArgumentException('One or more schemaless columns do not contain importable_column_id.');
        }

        return $value->keyBy('importable_column_id')->toJson();
    }
}
