<?php

namespace Tests\Unit\Traits;

trait WithClientCredentials
{
    /** @var array|null */
    protected $clientAuthorizationHeader;

    protected function setUpClientCredentials(): void
    {
        $attributes = head(config('auth.client_credentials'));

        $attributes = array_merge($attributes, [
            'grant_type' => 'client_credentials',
            'scope' => '*'
        ]);

        $response = $this->postJson(url('oauth/token'), $attributes);

        $clientAuth = ['Authorization' => 'Bearer ' . $response->json('access_token')];

        $this->clientAuthorizationHeader = $clientAuth;
    }
}
