<?php

namespace App\Services\VendorServices;

use Illuminate\Cache\Repository as Cache;

class CachingOauthClient
{
    const TOKEN_CACHE_KEY = 'vs::oauth';

    public function __construct(protected OauthClient $oauthClient,
                                protected Cache       $cache)
    {
    }

    public function getAccessToken(): string
    {
        return $this->cache->get(self::TOKEN_CACHE_KEY, function () {

            $result = $this->oauthClient->issueAccessToken();

            $this->cache->put(self::TOKEN_CACHE_KEY, $result->getAccessToken(), $result->getTtl());

            return $result->getAccessToken();
        });
    }

    public function forgetAccessToken(): static
    {
        return tap($this, function () {
            $this->cache->forget(self::TOKEN_CACHE_KEY);
        });
    }
}