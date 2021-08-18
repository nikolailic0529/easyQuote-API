<?php

namespace App\Http\Client;

use App\Contracts\LoggerAware;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function tap;

class LoggingClientFactory extends Factory implements LoggerAware
{
    protected LoggerInterface $logger;

    protected array $messageFormats = [
        'REQUEST: {method} - {uri} - HTTP/{version} - {req_headers} - {req_body}',
        'RESPONSE: {code} - {res_body}',
    ];

    public function __construct(Dispatcher $dispatcher = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($dispatcher);

        $this->logger = $logger ?? new NullLogger();
    }


    protected function newPendingRequest(): PendingRequest
    {
        return tap(parent::newPendingRequest(), function (PendingRequest $request) {
            $this->setupLoggingHandler($request);
        });
    }

    protected function setupLoggingHandler(PendingRequest $request): void
    {
        foreach (array_reverse($this->messageFormats) as $format) {
            $request->withMiddleware(
                Middleware::log(
                    $this->logger,
                    new MessageFormatter($format)
                )
            );
        }
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }
}