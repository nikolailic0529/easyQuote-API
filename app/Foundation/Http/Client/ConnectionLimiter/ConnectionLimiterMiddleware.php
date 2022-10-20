<?php

namespace App\Foundation\Http\Client\ConnectionLimiter;

use Psr\Http\Message\RequestInterface;

class ConnectionLimiterMiddleware
{
    private function __construct(
        protected readonly ConnectionLimiter $connLimiter
    ) {
    }

    public static function max(int $limit, Store $store = null): ConnectionLimiterMiddleware
    {
        $rateLimiter = new ConnectionLimiter(
            limit: $limit,
            store: $store ?? new InMemoryStore(),
        );

        return new static($rateLimiter);
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $this->connLimiter->handle(function () use ($request, $handler, $options) {
                return $handler($request, $options);
            });
        };
    }
}
