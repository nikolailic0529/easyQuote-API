<?php

use App\Domain\FailureReport\Facades\Failure;

if (!function_exists('customlog')) {
    /**
     * Log a message to the logs.
     *
     * @return \App\Domain\Log\Contracts\Logger
     */
    function customlog()
    {
        if (func_num_args() > 0) {
            return app('customlogger')->log(...func_get_args());
        }

        return app('customlogger');
    }
}
