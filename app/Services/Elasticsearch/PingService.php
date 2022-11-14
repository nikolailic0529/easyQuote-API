<?php

namespace App\Services\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

class PingService
{
    public function __construct(
        protected readonly Client $client,
    ) {
    }

    /**
     * @param  callable|null  $onPing
     * @return void
     * @throws NoNodesAvailableException
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function waitUntilAvailable(int $retries = 15, callable $onPing = null): void
    {
        $onPing ??= static function (): void {
        };

        $client = $this->client;

        retry(
            times: $retries,
            callback: static function () use ($client, $onPing): bool {
                $onPing();

                return $client->ping();
            },
            sleepMilliseconds: static fn(int $retries): int => (int) pow(2, $retries - 1) * 1000,
            when: static fn(\Throwable $e): bool => $e instanceof NoNodesAvailableException
        );
    }
}