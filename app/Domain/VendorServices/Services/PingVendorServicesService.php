<?php

namespace App\Domain\VendorServices\Services;

use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PingVendorServicesService implements LoggerAware
{
    protected LoggerInterface $logger;

    public function __construct(protected CachingOauthClient $oauthClient,
                                protected Http $http,
                                protected Config $config,
                                LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function ping(): bool
    {
        $token = $this->oauthClient->getAccessToken();

        $response = $this->http->asJson()
            ->withToken($token)
            ->get($this->config->get('services.vs.url'));

        if ($response->ok()) {
            $this->logger->info('VendorServices API: ok.');

            return true;
        }

        $this->logger->error('VendorServices API: fault.', [
            'url' => $this->config->get('services.vs.url'),
            'response' => $response->json(),
        ]);

        return false;
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }
}
