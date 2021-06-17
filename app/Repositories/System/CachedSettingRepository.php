<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\SettingRepository;
use App\Models\System\SystemSetting;
use App\Repositories\System\Exceptions\SettingException;
use Illuminate\Contracts\Cache\Repository as Cache;

class CachedSettingRepository implements SettingRepository
{
    protected Cache $cache;

    protected static string $getValueCacheKeyPrefix = 'get-system-setting';
    protected static string $hasValueCacheKeyPrefix = 'has-system-setting';

    /**
     * CachedSettingRepository constructor.
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritDoc
     * @throws \App\Repositories\System\Exceptions\SettingException
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * @inheritDoc
     * @throws \App\Repositories\System\Exceptions\SettingException
     */
    public function offsetUnset($offset)
    {
        return $this->set($offset, null);
    }

    public function get(string $key, $default = null)
    {
        if (false === $this->has($key)) {
            return value($default);
        }

        return $this->cache->rememberForever(static::$getValueCacheKeyPrefix.":$key", function () use ($key) {

            return SystemSetting::query()->where('key', $key)->value('value');

        });
    }

    /**
     * @throws \App\Repositories\System\Exceptions\SettingException
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
        return (bool)$this->cache->rememberForever(static::$hasValueCacheKeyPrefix.":$key", function () use ($key) {

            return SystemSetting::query()->where('key', $key)->exists();

        });
    }

    protected function rememberValueOfKey(string $key, $value): bool
    {
        return $this->cache->put(static::$getValueCacheKeyPrefix.":$key", $value);
    }
}