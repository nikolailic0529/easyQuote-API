<?php

namespace Tests\Unit\Traits;

trait WithClientCredentials
{
    /** @var array */
    protected $clientAuthHeader;

    protected function setUpClientCredentials(): void
    {
        $attributes = head(config('auth.client_credentials'));

        $attributes = array_merge($attributes, [
            'grant_type' => 'client_credentials',
            'scope' => '*'
        ]);

        $response = $this->withoutMiddleware()->postJson(url('oauth/token'), $attributes);

        $accessToken = $response->json('access_token');

        $this->clientAuthHeader = ['Authorization' => 'Bearer ' . $accessToken];
    }
}
