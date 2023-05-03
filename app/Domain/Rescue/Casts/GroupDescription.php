<?php

namespace App\Domain\Rescue\Casts;

use App\Domain\Rescue\DataTransferObjects\RowsGroup;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Collection;

class GroupDescription implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param mixed                               $value
     * @param array                               $attributes
     *
     * @return mixed
     */
    public function get($model, $key, $value, $attributes)
    {
        if (is_string($value)) {
            $value = $model->fromJson($value ?? []);
        }

        if ($value instanceof Collection) {
            return $value;
        }

        $value ??= [];

        return Collection::wrap($value)->mapInto(RowsGroup::class);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param array                               $value
     * @param array                               $attributes
     *
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        if (is_null($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = Collection::wrap(json_decode($value, true))->map(function ($group) {
                try {
                    return new RowsGroup($group);
                } catch (\Throwable $e) {
                }
            });
        }

        if (!$value instanceof Collection) {
            throw JsonEncodingException::forAttribute($model, $key, 'The value must be an instance of \Illuminate\Support\Collection');
        }

        if (Collection::wrap($value)->contains(fn ($group) => !$group instanceof RowsGroup)) {
            throw JsonEncodingException::forAttribute($model, $key, 'The Collection must contain only values instance of \App\DTO\RowsGroup');
        }

        return $value->toJson();
    }
}
