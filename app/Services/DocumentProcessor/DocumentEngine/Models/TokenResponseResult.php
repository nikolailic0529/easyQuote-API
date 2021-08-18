<?php

namespace App\Services\DocumentProcessor\DocumentEngine\Models;

final class TokenResponseResult
{
    public function __construct(
        protected string $accessToken = '',
        protected int $expiresIn = 0,
    )
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
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }
}