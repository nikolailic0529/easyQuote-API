<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
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
        if($exception instanceof ModelNotFoundException) {
            switch ($exception->getModel()) {
                case \App\Models\Quote\Margin\CountryMargin::class:
                    abort(404, __('margin.404'));
                    break;
                case \App\Models\Quote\Discount\MultiYearDiscount::class:
                case \App\Models\Quote\Discount\PrePayDiscount::class:
                case \App\Models\Quote\Discount\PromotionalDiscount::class:
                case \App\Models\Quote\Discount\SND::class:
                    abort(404, __('discount.404'));
                    break;
            }
        }

        return parent::render($request, $exception);
    }
}
