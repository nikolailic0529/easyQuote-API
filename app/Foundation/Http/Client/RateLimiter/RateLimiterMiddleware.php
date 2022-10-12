<?php

namespace App\Foundation\Http\Client\RateLimiter;

use Psr\Http\Message\RequestInterface;

class RateLimiterMiddleware
{
    private function __construct(
        protected readonly RateLimiter $rateLimiter
    ) {
    }

    public static function perSecond(int $limit, Store $store = null, Deferrer $deferrer = null): RateLimiterMiddleware
    {
        $rateLimiter = new RateLimiter(
            limit: $limit,
            timeFrame: TimeFrameEnum::Second,
            store: $store ?? new InMemoryStore(),
            deferrer: $deferrer ?? new SleepDeferrer()
        );

        return new static($rateLimiter);
    }

    public static function perMinute(int $limit, Store $store = null, Deferrer $deferrer = null): RateLimiterMiddleware
    {
        $rateLimiter = new RateLimiter(
            limit: $limit,
            timeFrame: TimeFrameEnum::Minute,
            store: $store ?? new InMemoryStore(),
            deferrer: $deferrer ?? new SleepDeferrer()
        );

        return new static($rateLimiter);
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $this->rateLimiter->handle(function () use ($request, $handler, $options) {
                return $handler($request, $options);
            });
        };
    }
}
