<?php

namespace App\Foundation\Http\Client\RateLimiter;

class SleepDeferrer implements Deferrer
{
    public function getCurrentTime(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
