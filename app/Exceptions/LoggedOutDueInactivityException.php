<?php

namespace App\Exceptions;

use Exception;

class LoggedOutDueInactivityException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json(['message' => LO_00, 'code' => 'LO_00']);
    }
}
