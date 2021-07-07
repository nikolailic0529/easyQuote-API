<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use JetBrains\PhpStorm\Pure;
use Laravel\Passport\{Client,
    ClientRepository,
    Http\Middleware\CheckClientCredentials as PassportClientCredentials,
    TokenRepository};
use League\OAuth2\{Server\Exception\OAuthServerException, Server\ResourceServer};
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class CheckClientCredentials extends PassportClientCredentials
{
    #[Pure]
    public function __construct(ResourceServer $server,
                                TokenRepository $repository,
                                protected ClientRepository $clientRepository)
    {
        parent::__construct($server, $repository);
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed ...$scopes
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException|\Laravel\Passport\Exceptions\MissingScopeException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        $psr = (new PsrHttpFactory(
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory
        ))->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);

            $clientId = $psr->getAttribute('oauth_client_id');

            /** @var Client|null $client */
            $client = $this->clientRepository->find($clientId);

            $request->request->add([
                'client_id' => $client?->getKey(),
                'client_name' => $client?->name,
            ]);

        } catch (OAuthServerException $e) {
            throw new AuthenticationException;
        }

        $this->validate($psr, $scopes);

        return $next($request);
    }
}
