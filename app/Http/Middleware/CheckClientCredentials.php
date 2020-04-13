<?php

namespace App\Http\Middleware;

use App\Contracts\Repositories\System\ClientCredentialsInterface;
use Laravel\Passport\{
    TokenRepository,
    Http\Middleware\CheckClientCredentials as PassportClientCredentials
};
use League\OAuth2\{
    Server\ResourceServer,
    Server\Exception\OAuthServerException
};
use Laminas\Diactoros\{
    ResponseFactory,
    ServerRequestFactory,
    StreamFactory,
    UploadedFileFactory,
};
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Illuminate\Auth\AuthenticationException;
use Psr\Http\Message\ServerRequestInterface;
use Closure;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        $psr = (new PsrHttpFactory(
            new ServerRequestFactory,
            new StreamFactory,
            new UploadedFileFactory,
            new ResponseFactory
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

    protected function checkClientName(ServerRequestInterface $psr, $scopes)
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
