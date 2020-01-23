<?php

if (!function_exists('error_response')) {
    function error_response(string $details, string $code, int $status)
    {
        return app('http.service')->makeErrorResponse(...func_get_args());
    }
}

if (!function_exists('error_abort')) {
    function error_abort(string $details, string $code, int $status)
    {
        abort(error_response($details, $code, $status));
    }
}

if (!function_exists('error_abort_if')) {
    function error_abort_if($boolean, $details, $code, $status)
    {
        if ($boolean) {
            error_abort($details, $code, $status);
        }
    }
}
