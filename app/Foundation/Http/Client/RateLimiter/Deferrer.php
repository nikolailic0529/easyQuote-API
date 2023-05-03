<?php

namespace App\Foundation\Http\Client\RateLimiter;

interface Deferrer
{
    public function getCurrentTime(): int;

    public function sleep(int $milliseconds): void;
}
