<?php

namespace App\Foundation\Error;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
        \League\OAuth2\Server\Exception\OAuthServerException::class,
    ];

    protected array $dontReportMail = [
        \Illuminate\Http\Client\RequestException::class,
        \GuzzleHttp\Exception\RequestException::class,
        \App\Foundation\Mail\Exceptions\MailRateLimitException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        if (!$this->container->bound(ErrorReporter::class)) {
            return;
        }

        $this->reportable(function (\Throwable $e): void {
            $this->container->make(ErrorReporter::class)($e);
        });
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  Request  $request
     *
     * @throws BindingResolutionException
     */
    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        customlog(['ErrorCode' => 'EQ_INV_DP_01'], $exception->errors());

        $http = $this->container->make('http.service');

        return $http->invalidJson($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  Request  $request
     * @throws BindingResolutionException
     */
    protected function unauthenticated(mixed $request, AuthenticationException $exception): Response
    {
        $http = $this->container->make('http.service');

        return $http->makeErrorResponse(EQ_UA_01, 'EQ_UA_01', 401);
    }

    /**
     * Convert the given exception to an array.
     *
     * @throws BindingResolutionException
     */
    protected function convertExceptionToArray(\Throwable $e): array
    {
        $http = $this->container->make('http.service');

        if ($http->isInvalidRequestException($e)) {
            return $http->convertErrorToArray(EQ_INV_REQ_01, 'EQ_INV_REQ_01', true);
        }

        return parent::convertExceptionToArray($e);
    }
}
