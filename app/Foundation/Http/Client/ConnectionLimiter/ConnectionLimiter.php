<?php

namespace App\Foundation\Http\Client\ConnectionLimiter;

use GuzzleHttp\Promise\Create;
use Illuminate\Support\Carbon;

class ConnectionLimiter
{
    public function __construct(
        protected readonly int $limit,
        protected readonly Store $store,
        protected readonly int $waitSeconds = 30,
        protected readonly int $sleepMilliseconds = 250,
    ) {
    }

    public function handle(callable $callback): mixed
    {
        $starting = $this->currentTime();

        while (!$this->tryAcquire()) {
            usleep($this->sleepMilliseconds * 1000);

            if ($this->currentTime() - $this->waitSeconds >= $starting) {
                throw new ConnectionTimeoutException;
            }
        }

        return $callback()->then(
            function (mixed $response): mixed {
                $this->store->decrement();

                return $response;
            },
            function (mixed $reason): mixed {
                $this->store->decrement();

                return Create::rejectionFor($reason);
            }
        );
    }

    protected function currentTime(): int
    {
        return Carbon::now()->getTimestamp();
    }

    protected function tryAcquire(): bool
    {
        return $this->store->getMutex(5)->get(function (): bool {
            if ($this->store->get() < $this->limit) {
                $this->store->increment();

                return true;
            }

            return false;
        });
    }
}
