<?php

namespace App\Foundation\Http\Client\ConnectionLimiter;

use Illuminate\Contracts\Cache\Lock;

interface Store
{
    public function get(): int;

    public function increment(): int;

    public function decrement(): int;

    public function getMutex(int $seconds = 0): Lock;
}
