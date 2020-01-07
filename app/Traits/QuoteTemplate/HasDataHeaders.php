<?php

namespace App\Traits\QuoteTemplate;

use Illuminate\Support\Collection;

trait HasDataHeaders
{
    public function initializeHasDataHeaders()
    {
        $this->fillable = array_merge($this->fillable, ['data_headers']);
        $this->casts = array_merge($this->casts, ['data_headers' => 'collection']);
    }

    public function getDataHeadersAttribute($value): Collection
    {
        $headers = collect(json_decode($value, true));
        $keys = $headers->pluck('key')->flip();

        $defaultHeaders = static::defaultDataHeaders()
            ->reject(function ($value, $key) use ($keys) {
                return $keys->has($key);
            })
            ->map(function ($value, $key) {
                return compact('key', 'value');
            });

        return $headers->merge($defaultHeaders)->values();
    }

    public function setDataHeadersAttribute($value)
    {
        $this->attributes['data_headers'] = json_encode($value);
    }

    public function dataHeader(string $key): string
    {
        $headers = $this->data_headers->keyBy('key');

        return data_get($headers, "{$key}.value", __('template.data_headers.' . $key));
    }

    public static function defaultDataHeaders(): Collection
    {
        return collect(__('template.data_headers'));
    }
}
