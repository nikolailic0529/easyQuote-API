<?php

use App\Facades\Failure;
use App\Mail\FailureReportMail;
use Illuminate\Support\Facades\Mail;

if (!function_exists('customlog')) {
    /**
     * Log a message to the logs.
     *
     * @return \App\Contracts\Services\Logger
     */
    function customlog()
    {
        if (func_num_args() > 0) {
            return app('customlogger')->log(...func_get_args());
        }

        return app('customlogger');
    }
}

if (!function_exists('report_failure')) {
    function report_failure(Throwable $exception)
    {
        if (app()->runningInConsole()) {
            return;
        }

        $failure = Failure::helpFor($exception);

        Mail::send(new FailureReportMail($failure, setting('failure_report_recipients')));

        customlog(['ErrorCode' => 'UNE_01'], ['ErrorDetails' => $failure->message]);
    }
}
