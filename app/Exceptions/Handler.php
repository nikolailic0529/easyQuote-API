<?php

namespace App\Exceptions;

use App\Contracts\Factories\FailureInterface;
use App\Contracts\Services\Logger;
use App\Mail\FailureReportMail;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;
use Throwable;

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
        \League\OAuth2\Server\Exception\OAuthServerException::class
    ];

    protected $dontReportMail = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        parent::report($e);

        if ($this->shouldReportMail($e)) {
            $this->reportToMail($e);
        }
    }

    /**
     * @param Throwable $exception
     * @throws Throwable
     * @throws BindingResolutionException
     */
    protected function reportToMail(Throwable $exception): void
    {
        try {
            $failure = $this->container->make(FailureInterface::class)->helpFor($exception);

            /** @var \Illuminate\Contracts\Mail\Mailer $mailer */
            $mailer = $this->container->make('mailer');

            $mailer->send(
                new FailureReportMail($failure, setting('failure_report_recipients'))
            );
        } catch (Throwable $e) {
            try {
                $logger = $this->container->make(LoggerInterface::class);
            } catch (\Exception $e) {
                throw $e;
            }

            $logger->error(
                $e->getMessage(),
                array_merge(
                    $this->exceptionContext($e),
                    $this->context(),
                    ['exception' => $e]
                )
            );
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable $exception
     * @return \Illuminate\Http\Response
     * @throws Throwable
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }

    /**
     * Determine if the exception is in the "do not report mail" list.
     *
     * @param Throwable $e
     * @return bool
     */
    protected function shouldntReportMail(Throwable $e)
    {
        $dontReport = array_merge($this->dontReportMail, $this->dontReport, $this->internalDontReport);

        return !is_null(Arr::first($dontReport, fn($type) => $e instanceof $type));
    }

    /**
     * Whether the given exception should be reported to email.
     *
     * @param Throwable $e
     * @return bool
     */
    public function shouldReportMail(Throwable $e)
    {
        return !$this->shouldntReportMail($e);
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Validation\ValidationException $exception
     * @return \Illuminate\Http\JsonResponse
     * @throws BindingResolutionException
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        $customLogger = $this->container->make(Logger::class);

        $customLogger->log(['ErrorCode' => 'EQ_INV_DP_01'], $exception->errors());

        $http = $this->container->make('http.service');

        return $http->invalidJson($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Auth\AuthenticationException $exception
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws BindingResolutionException
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $http = $this->container->make('http.service');

        return $http->makeErrorResponse(EQ_UA_01, 'EQ_UA_01', 401);
    }

    /**
     * Convert the given exception to an array.
     *
     * @param Throwable $exception
     * @return array
     * @throws BindingResolutionException
     */
    protected function convertExceptionToArray(Throwable $exception)
    {
        $http = $this->container->make('http.service');

        if ($http->isInvalidRequestException($exception)) {
            return $http->convertErrorToArray(EQ_INV_REQ_01, 'EQ_INV_REQ_01', true);
        }

        return parent::convertExceptionToArray($exception);
    }
}
