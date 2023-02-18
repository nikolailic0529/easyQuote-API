<?php

namespace App\Domain\FailureReport\Contracts;

interface RateLimiter
{
    public function attempt(string $key, callable $callback): mixed;
}
