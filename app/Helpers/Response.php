<?php

if (!function_exists('error_response')) {
    function error_response(string $constant, int $status)
    {
        return response()->json([
            'message' => constant($constant),
            'code' => $constant
        ], $status);
    }
}

if (!function_exists('error_abort')) {
    function error_abort(string $constant, int $status)
    {
        abort(error_response($constant, $status));
    }
}

if (!function_exists('error_abort_if')) {
    function error_abort_if($boolean, string $constant, int $status)
    {
        abort_if($boolean, error_response($constant, $status));
    }
}
