<?php

namespace App\Services\VendorServices\Models;

use DateInterval;

final class OauthResult
{
    public function __construct(protected string        $accessToken,
                                protected DateInterval $ttl)
    {
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return DateInterval
     */
    public function getTtl(): DateInterval
    {
        return $this->ttl;
    }
}