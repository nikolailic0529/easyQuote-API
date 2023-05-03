<?php

namespace App\Domain\DocumentEngine;

use App\Domain\DocumentEngine\Concerns\DocumentEngineClient;
use App\Domain\DocumentEngine\Exceptions\ClientAuthException;
use App\Domain\DocumentProcessing\DocumentEngine\Models\TokenResponseResult;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class OauthClient implements DocumentEngineClient, LoggerAware
{
    const ENDPOINT = 'v1/api/oauth/token';
    const TOKEN_KEY = 'document_engine.access_token';

    protected LoggerInterface $logger;

    #[Pure]
    public function __construct(protected Config $config,
                                protected Cache $cache,
                                protected HttpFactory $httpFactory,
                                LoggerInterface|null $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws \App\Domain\DocumentEngine\Exceptions\ClientAuthException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getAccessToken(): string
    {
        if (false === is_null($token = $this->cache->get(self::TOKEN_KEY))) {
            return $token;
        }

        $result = $this->issueClientToken();

        return tap($result->getAccessToken(), function () use ($result) {
            $this->cache->put(key: self::TOKEN_KEY, value: $result->getAccessToken(), ttl: $result->getExpiresIn());
        });
    }

    public function forgetAccessToken(): bool
    {
        return $this->cache->forget(self::TOKEN_KEY);
    }

    /**
     * @throws ClientAuthException
     */
    protected function issueClientToken(): TokenResponseResult
    {
        $response = $this->httpFactory
            ->baseUrl($this->getBaseUrl())
            ->post(self::ENDPOINT, [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
            ]);

        if ($response->failed()) {
            throw ClientAuthException::requestFailed($response->status());
        }

        if (is_null($response->json('access_token')) || is_null($response->json('expires_in'))) {
            throw ClientAuthException::missingResponsePayload();
        }

        return new TokenResponseResult(
            accessToken: (string) $response->json('access_token'),
            expiresIn: (int) $response->json('expires_in'),
        );
    }

    protected function getClientId(): string
    {
        return $this->config->get('services.document_api.client_id') ?? '';
    }

    protected function getClientSecret(): string
    {
        return $this->config->get('services.document_api.client_secret') ?? '';
    }

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.document_api.url') ?? '';
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }
}
