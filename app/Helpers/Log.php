<?php

use App\Facades\Failure;
use App\Mail\FailureReportMail;
use Illuminate\Support\Facades\Mail;

if (!function_exists('report_logger')) {
    function report_logger()
    {
        if (func_num_args() > 0) {
            return app('report.logger')->log(...func_get_args());
        }

        return app('report.logger');
    }
}

if (!function_exists('report_failure')) {
    function report_failure(\Throwable $exception)
    {
        if (app()->runningInConsole()) {
            return;
        }

        $failure = Failure::helpFor($exception);

        Mail::send(new FailureReportMail($failure, setting('failure_report_recipients')));

        report_logger(['ErrorCode' => 'UNE_01'], ['ErrorDetails' => $failure->message]);
    }
}
