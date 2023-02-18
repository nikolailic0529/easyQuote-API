<?php

namespace App\Domain\FailureReport;

use Illuminate\Cache\RateLimiter as DelegateRateLimiter;

class DefaultRateLimiter implements \App\Domain\FailureReport\Contracts\RateLimiter
{
    public function __construct(
        protected readonly DelegateRateLimiter $delegate,
    ) {
    }

    public function attempt(string $key, callable $callback): mixed
    {
        return $this->delegate->attempt(key: $key, maxAttempts: 1, callback: $callback);
    }
}
