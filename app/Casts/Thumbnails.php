<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Thumbnails implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $value, $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = $model->fromJson($value ?? []);
        }

        return Collection::wrap($value)->map(fn ($thumb) => Str::contains($thumb, ['svg+xml', 'http']) ? $thumb : asset("storage/{$thumb}"))->toArray();
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        if (is_null($value)) {
            return $value;
        }

        $value = json_encode($value);

        if ($value === false) {
            throw JsonEncodingException::forAttribute(
                $model, $key, json_last_error_msg()
            );
        }

        return $value;
    }
}
