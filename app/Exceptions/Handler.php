<?php

namespace App\Exceptions;

use App\Mail\FailureReportMail;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Exception, Failure, Arr;

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

    protected $dontReportMail = [
        \App\Exceptions\AlreadyAuthenticatedException::class,
        \App\Exceptions\MustChangePasswordException::class,
        \App\Exceptions\LoggedOutDueInactivityException::class
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

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);

        $this->failureReport($exception);
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

    public function failureReport(Exception $exception)
    {
        if ($this->shouldntReportMail($exception)) {
            return;
        }

        $failure = Failure::helpFor($exception);

        Mail::send(new FailureReportMail($failure, app('user.repository')->failureReportRecepients()));

        report_logger(['ErrorCode' => 'UNE_01'], ['ErrorDetails' => $failure->message]);
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
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        if ($request->is('api/s4/*')) {
            return $this->invalidJsonForS4($request, $exception);
        }

        return response()->json([
            'message' => head(head($exception->errors())),
            'errors' => $exception->errors(),
        ], $exception->status);
    }


    /**
     * Convert a validation exception into a JSON response for S4 service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJsonForS4($request, ValidationException $exception)
    {
        return response()->json([
            'ErrorUrl' => $request->fullUrl(),
            'ErrorCode' => 'INVDP_01',
            'Error' => [
                'headers' => [],
                'original' => $exception->errors(),
                'exception' => null
            ],
            'ErrorDetails' => INVDP_01
        ], $exception->status);
    }
}
