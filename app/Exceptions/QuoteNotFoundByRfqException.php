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
        return response()->json(['message' => config('constants.EQ_NF_01'), 'code' => 'EQ_NF_01'], 404);
    }
}
