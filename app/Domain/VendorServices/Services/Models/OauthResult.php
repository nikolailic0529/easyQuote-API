<?php

namespace App\Domain\VendorServices\Services\Models;

final class OauthResult
{
    public function __construct(protected string $accessToken,
                                protected \DateInterval $ttl)
    {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTtl(): \DateInterval
    {
        return $this->ttl;
    }
}
