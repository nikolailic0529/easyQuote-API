<?php

namespace App\Casts;

use App\DTO\HpeContractService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Collection;

class HpeServicesCast implements CastsAttributes
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
        if (is_string($value)) {
            $value = $model->fromJson($value ?? []);
        }

        $value ??= [];

        return Collection::wrap($value)->mapInto(HpeContractService::class);
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
