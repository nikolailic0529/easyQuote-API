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
        return error_response('LO_00', 401);
    }
}
