<?php

namespace App\Foundation\Http\Client\RateLimiter;

interface Store
{
    public function get(): array;

    public function push(int $timestamp, int $limit): void;
}
