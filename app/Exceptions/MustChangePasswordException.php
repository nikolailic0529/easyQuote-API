<?php

namespace App\Exceptions;

use Exception;

class MustChangePasswordException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return error_response('MCP_00', 422);
    }
}
