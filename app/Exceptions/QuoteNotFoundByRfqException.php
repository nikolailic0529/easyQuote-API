<?php

namespace App\Exceptions;

use Exception;

class QuoteNotFoundByRfqException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return error_response('EQ_NF_01', 404);
    }
}
