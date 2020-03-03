<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\ClientCredentialsInterface;
use Arr;

class ClientCredentialsRepository implements ClientCredentialsInterface
{
    protected static $cachePrefix = 'client_credentials';

    public function find(string $id, ?string $attribute = null)
    {
        if (($credentials = cache(static::getCacheKey($id))) !== null) {
            return $this->getCredentialsAttribute($credentials, $attribute);
        }

        $credentials = Arr::where($this->all(), fn ($credentials) => data_get($credentials, 'client_id') === $id);

        $credentials = $this->setCredentialsKey($credentials);

        cache()->forever(static::getCacheKey($id), $credentials);

        return $this->getCredentialsAttribute($credentials, $attribute);
    }

    public function all(): array
    {
        return config('auth.client_credentials');
    }

    private function setCredentialsKey(array $credentials): array
    {
        $key = key($credentials);
        $credentials = head($credentials);
        $credentials['client_key'] = $key;

        return $credentials;
    }

    private function getCredentialsAttribute(array $credentials, ?string $attribute = null)
    {
        if (isset($attribute)) {
            return data_get($credentials, $attribute);
        }

        return $credentials;
    }

    private static function getCacheKey(string $id)
    {
        return static::$cachePrefix.':'.$id;
    }
}
