<?php

namespace App\Http\Middleware;

use App\Contracts\Repositories\System\ClientCredentialsInterface;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\{Http\Middleware\CheckClientCredentials as PassportClientCredentials, TokenRepository};
use League\OAuth2\{Server\Exception\OAuthServerException, Server\ResourceServer};
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class CheckClientCredentials extends PassportClientCredentials
{
    protected ClientCredentialsInterface $credentials;

    public function __construct(ResourceServer $server, TokenRepository $repository, ClientCredentialsInterface $credentials)
    {
        parent::__construct($server, $repository);

        $this->credentials = $credentials;
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

            $client_name = $this->checkClientName($psr, $scopes);

            $request->request->set('client_id', $psr->getAttribute('oauth_client_id'));
            $request->request->set('client_name', $client_name);
        } catch (OAuthServerException $e) {
            throw new AuthenticationException;
        }

        $this->validate($psr, $scopes);

        return $next($request);
    }

    protected function checkClientName(ServerRequestInterface $psr, $scopes): ?string
    {
        if (blank($scopes)) {
            return null;
        }

        $credentials = $this->credentials->find($psr->getAttribute('oauth_client_id'));
        $client_key = data_get($credentials, 'client_key');
        $client_name = data_get($credentials, 'client_name');

        throw_unless(isset(array_flip($scopes)[$client_key]), AuthenticationException::class);

        return $client_name;
    }
}
