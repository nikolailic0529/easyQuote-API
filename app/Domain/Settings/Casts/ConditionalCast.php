<?php

namespace App\Domain\Settings\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class ConditionalCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param mixed                               $value
     * @param array                               $attributes
     */
    public function get($model, $key, $value, $attributes): mixed
    {
        return match ($attributes['type'] ?? null) {
            'boolean' => (bool) $value,
            'string' => (string) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'decimal' => number_format((float) $value, 2, '.', ''),
            'array' => is_array($value) ? $value : json_decode($value, true),
            'datetime' => (string) Carbon::parse($value),
            default => $value,
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param array                               $value
     * @param array                               $attributes
     */
    public function set($model, $key, $value, $attributes): mixed
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
