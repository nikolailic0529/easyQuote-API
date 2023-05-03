<?php

namespace App\Domain\DocumentProcessing\DocumentEngine\Models;

final class TokenResponseResult
{
    public function __construct(
        protected string $accessToken = '',
        protected int $expiresIn = 0,
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }
}
