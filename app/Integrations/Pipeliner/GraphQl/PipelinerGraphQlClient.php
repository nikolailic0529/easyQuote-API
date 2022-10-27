<?php

namespace App\Integrations\Pipeliner\GraphQl;

use App\Contracts\LoggerAware;
use App\Foundation\Http\Client\ConnectionLimiter\ConnectionLimiterMiddleware;
use App\Foundation\Http\Client\ConnectionLimiter\Store as ConnStore;
use App\Foundation\Http\Client\GuzzleReactBridge\CurlMultiHandler;
use App\Foundation\Http\Client\RateLimiter\RateLimiterMiddleware;
use App\Foundation\Http\Client\RateLimiter\Store as RateStore;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function tap;

class PipelinerGraphQlClient extends Factory implements LoggerAware
{
    protected array $messageFormats = [
        'REQUEST: {method} - {uri} - HTTP/{version} - {req_headers} - {req_body}',
        'RESPONSE: {code} - {res_body}',
    ];

    public function __construct(
        protected readonly Config $config,
        protected readonly Cache $cache,
        protected readonly LockProvider $lockProvider,
        protected readonly RateStore $rateLimiterStore,
        protected readonly ConnStore $connLimiterStore,
        protected LoggerInterface $logger = new NullLogger(),
        Dispatcher $dispatcher = null
    ) {
        parent::__construct($dispatcher);
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    protected function newPendingRequest(): PendingRequest
    {
        return tap(parent::newPendingRequest(), function (PendingRequest $request): void {
            $request
                ->withBasicAuth(username: $this->getUsername(), password: $this->getPassword())
                ->withHeaders([
                    'Webhook-Skip-Key' => head($this->config->get('pipeliner.webhook.options.skip_keys', [])),
                ])
                ->asJson()
                ->acceptJson()
                ->timeout(seconds: 60)
                ->retry(times: 3, sleep: 100);

            $this->setupRateLimiterMiddleware($request);
            $this->setupLoggingHandler($request);

            $request->setHandler(new CurlMultiHandler());
        });
    }

    public function buildSpaceEndpoint(): string
    {
        return strtr((string) $this->config->get('services.pipeliner.space_endpoint'),
            ['{space_id}' => $this->getSpaceId()]);
    }

    protected function getServiceUrl(): string
    {
        return $this->config->get('services.pipeliner.url');
    }

    protected function getUsername(): string
    {
        return (string) $this->config->get('services.pipeliner.username');
    }

    protected function getPassword(): string
    {
        return (string) $this->config->get('services.pipeliner.password');
    }

    protected function getSpaceId(): string
    {
        return (string) $this->config->get('services.pipeliner.space_id');
    }

    protected function setupRateLimiterMiddleware(PendingRequest $request): void
    {
        $request->withMiddleware(
            RateLimiterMiddleware::perMinute(
                limit: (int) $this->config->get('pipeliner.client.throttle.rpm'),
                store: $this->rateLimiterStore,
            )
        );
    }

    protected function setupLoggingHandler(PendingRequest $request): void
    {
        foreach (array_reverse($this->messageFormats) as $format) {
            $request->withMiddleware(
                middleware: Middleware::log(
                    logger: $this->logger,
                    formatter: new MessageFormatter($format)
                )
            );
        }
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }
}