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
        return response()->json(['message' => MCP_00, 'code' => 'MCP_00']);
    }
}
