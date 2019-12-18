<?php

if (!function_exists('report_logger')) {
    function report_logger()
    {
        if (func_num_args() > 0) {
            return app('report.logger')->log(...func_get_args());
        }

        return app('report.logger');
    }
}
