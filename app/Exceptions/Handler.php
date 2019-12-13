<?php

namespace App\Exceptions;

use App\Contracts\Repositories\UserRepositoryInterface;
use Exception;
use App\Mail\FailureReportMail;
use App\Models\User;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Mail;
use Arr;

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
        \Illuminate\Validation\ValidationException::class
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

        if ($this->shouldntReportMail($exception)) {
            return;
        }

        Mail::send(new FailureReportMail($exception, app('user.repository')->failureReportRecepients()));
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

        return ! is_null(Arr::first($dontReport, function ($type) use ($e) {
            return $e instanceof $type;
        }));
    }
}
