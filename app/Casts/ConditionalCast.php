<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class ConditionalCast implements CastsAttributes
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
        switch ($attributes['type'] ?? null) {
            case 'boolean':
                return (bool) $value;
                break;
            case 'string':
                return (string) $value;
                break;
            case 'integer':
                return (int) $value;
                break;
            case 'float':
                return (float) $value;
                break;
            case 'decimal':
                return number_format((float) $value, 2, '.', '');
                break;
            case 'array':
                return is_array($value) ? $value : json_decode($value, true);
                break;
            case 'datetime':
                return (string) Carbon::parse($value);
                break;
            default:
                return $value;
                break;
        }
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
        if (is_array($value)) {
            return json_encode($value);
        }       

        return $value;
    }
}
