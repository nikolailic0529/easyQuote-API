<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\SettingRepository;
use App\Models\System\SystemSetting;
use App\Repositories\System\Exceptions\SettingException;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedSettingRepository implements SettingRepository
{
    protected static string $getValueCacheKeyPrefix = 'get-system-setting';
    protected static string $hasValueCacheKeyPrefix = 'has-system-setting';

    public function __construct(
        protected Cache $cache
    ) {
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * @throws SettingException
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @throws SettingException
     */
    public function offsetUnset($offset): void
    {
        $this->set($offset, null);
    }

    public function get(string $key, $default = null): mixed
    {
        if (false === $this->has($key)) {
            return value($default);
        }

        return $this->cache->rememberForever(static::$getValueCacheKeyPrefix.":$key", function () use ($key): mixed {
            return SystemSetting::query()->where('key', $key)->value('value');
        });
    }

    /**
     * @throws SettingException
     */
    public function set(string $key, $value): bool
    {
        if (false === $this->has($key)) {
            throw SettingException::undefinedSettingKey($key);
        }

        SystemSetting::query()
            ->where('key', $key)
            ->update(['value' => $value]);

        return $this->rememberValueOfKey($key, $value);
    }

    public function has(string $key): bool
    {
        return (bool) $this->cache->rememberForever(static::$hasValueCacheKeyPrefix.":$key",
            function () use ($key): bool {
                return SystemSetting::query()->where('key', $key)->exists();
            });
    }

    protected function rememberValueOfKey(string $key, $value): bool
    {
        return $this->cache->put(static::$getValueCacheKeyPrefix.":$key", $value);
    }
}