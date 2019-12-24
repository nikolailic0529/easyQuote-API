<?php

if (!function_exists('error_response')) {
    function error_response(string $constant, string $code, int $status)
    {
        return response()->json([
            'message' => $constant,
            'error_code' => $code
        ], $status);
    }
}

if (!function_exists('error_abort')) {
    function error_abort(string $constant, string $code, int $status)
    {
        abort(error_response($constant, $code, $status));
    }
}

if (!function_exists('error_abort_if')) {
    function error_abort_if($boolean, $constant, $code, $status)
    {
        if ($boolean) {
            error_abort($constant, $code, $status);
        }
    }
}
