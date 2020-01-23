<?php

namespace App\Exceptions;

use App\Mail\FailureReportMail;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Facades\Failure;
use Illuminate\Support\{
    Arr,
    Facades\Mail
};
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Exception;

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
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);

        if ($this->shouldReportMail($exception)) {
            report_failure($exception);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }

    /**
     * Determine if the exception is in the "do not report mail" list.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function shouldntReportMail(Exception $e)
    {
        $dontReport = array_merge($this->dontReportMail, $this->dontReport, $this->internalDontReport);

        return !is_null(Arr::first($dontReport, function ($type) use ($e) {
            return $e instanceof $type;
        }));
    }

    /**
     * Determine if the exception should be reported by mail.
     *
     * @param  \Exception  $e
     * @return bool
     */
    public function shouldReportMail(Exception $e)
    {
        return !$this->shouldntReportMail($e);
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        report_logger(['ErrorCode' => 'EQ_INV_DP_01'], $exception->errors());

        $http = $this->container->make('http.service');

        return $http->invalidJson($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $http = $this->container->make('http.service');

        return $http->makeErrorResponse(EQ_UA_01, 'EQ_UA_01', 401);
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  \Exception  $e
     * @return array
     */
    protected function convertExceptionToArray(Exception $exception)
    {
        $http = $this->container->make('http.service');

        if ($http->isInvalidRequestException($exception)) {
            return $http->convertErrorToArray(EQ_INV_REQ_01, 'EQ_INV_REQ_01', true);
        }

        return parent::{__FUNCTION__}($exception);
    }
}
