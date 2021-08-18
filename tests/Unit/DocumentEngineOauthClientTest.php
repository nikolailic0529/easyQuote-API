<?php

namespace Tests\Unit;

use App\Services\DocumentEngine\Exceptions\ClientAuthException;
use App\Services\DocumentEngine\OauthClient;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as ClientFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class DocumentEngineOauthClientTest
 *
 * @group document-engine-api
 */
class DocumentEngineOauthClientTest extends TestCase
{
    /**
     * Test client issues client access token.
     *
     * @return void
     * @throws \App\Services\DocumentEngine\Exceptions\ClientAuthException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function test_client_issues_access_token()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        $this->partialMock(Cache::class, function (\Mockery\MockInterface $mock) use ($expiresInFromResponse, $accessTokenFromResponse) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs(OauthClient::TOKEN_KEY)
                ->atLeast()
                ->twice()
                ->andReturnValues([null, $accessTokenFromResponse]);

            $mock->shouldReceive('put')
                ->withArgs([OauthClient::TOKEN_KEY, $accessTokenFromResponse, $expiresInFromResponse])
                ->once()
                ->andReturnTrue();
        });

        /** @var OauthClient $oauthClient */
        $oauthClient = $this->app[OauthClient::class];

        $accessToken = $oauthClient->getAccessToken();

        $this->assertNotEmpty($accessToken);
        $this->assertSame($accessTokenFromResponse, $accessToken);

        $accessToken = $oauthClient->getAccessToken();

        $this->assertNotEmpty($accessToken);
        $this->assertSame($accessTokenFromResponse, $accessToken);
    }

    /**
     * Test client throws an exception on server error.
     *
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function test_client_throws_exception_on_server_error()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ], status: 500),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });


        /** @var OauthClient $oauthClient */
        $oauthClient = $this->app[OauthClient::class];

        $this->expectException(ClientAuthException::class);

        $oauthClient->getAccessToken();
    }

    /**
     * Test client throws an exception on missing payload in response.
     *
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function test_client_throws_exception_on_missing_payload_in_response()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $clientFactory->fakeSequence(
            OauthClient::ENDPOINT
        )->push(
            body: ['expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),],
        );

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });


        /** @var OauthClient $oauthClient */
        $oauthClient = $this->app[OauthClient::class];

        $this->expectException(ClientAuthException::class);

        $oauthClient->getAccessToken();
    }
}