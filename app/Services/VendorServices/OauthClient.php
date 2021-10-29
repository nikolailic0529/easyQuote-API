<?php

namespace App\Services\VendorServices;

use App\Services\VendorServices\Models\OauthResult;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;

class OauthClient
{
    const GRANT_TYPE = 'client_credentials';
    const SCOPE = '*';

    public function __construct(protected Config      $config,
                                protected HttpFactory $http)
    {
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function issueAccessToken(): OauthResult
    {
        $url = rtrim($this->getBaseUrl(), '/').'/'.ltrim($this->config->get('services.vs.token_route'), '/');

        $response = $this->http->post(
            url: $url,
            data: [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'grant_type' => self::GRANT_TYPE,
                'scope' => self::SCOPE,
            ]
        );

        $response->throw();

        $accessToken = $response->json('access_token');
        $expiresInSeconds = (int)$response->json('expires_in');
        $ttl = new \DateInterval("PT{$expiresInSeconds}S");

        return new OauthResult(
            accessToken: $accessToken,
            ttl: $ttl,
        );
    }

    protected function getClientId(): ?string
    {
        return $this->config->get('services.vs.client_id');
    }

    protected function getClientSecret(): ?string
    {
        return $this->config->get('services.vs.client_secret');
    }

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.vs.url') ?? '';
    }
}