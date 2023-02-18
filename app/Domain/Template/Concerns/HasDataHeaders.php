<?php

namespace App\Domain\Template\Concerns;

use Illuminate\Support\Collection;

/**
 * @property Collection $data_headers
 */
trait HasDataHeaders
{
    public function initializeHasDataHeaders()
    {
        $this->fillable = array_merge($this->fillable, ['data_headers']);
        $this->casts = array_merge($this->casts, ['data_headers' => 'collection']);
    }

    public function getDataHeadersAttribute($value): Collection
    {
        $headers = collect(json_decode($value, true))->keyBy('key');

        return static::defaultDataHeaders()
            ->map(fn ($header, $key) => [
                'key' => $key,
                'label' => $header['label'] ?? null,
                'value' => data_get($headers, "{$key}.value", $header['value'] ?? null),
            ]);
    }

    public function setDataHeadersAttribute($value)
    {
        $this->attributes['data_headers'] = json_encode($value);
    }

    public function dataHeader(string $key, ?string $fallback = null): ?string
    {
        $headers = $this->data_headers;
        $default = data_get(static::dataHeadersDictionary(), "{$key}.value");

        if (is_null($default) && is_string($fallback)) {
            $default = $fallback;
        }

        return data_get($headers, "{$key}.value", $default);
    }

    public static function defaultDataHeaders(): Collection
    {
        return collect(static::dataHeadersDictionary());
    }

    public static function defaultDataHeader(?string $key): string
    {
        return data_get(static::dataHeadersDictionary(), $key);
    }

    public static function dataHeaderKeys(): array
    {
        return array_keys(static::dataHeadersDictionary());
    }

    abstract public static function dataHeadersDictionary(): array;
}
