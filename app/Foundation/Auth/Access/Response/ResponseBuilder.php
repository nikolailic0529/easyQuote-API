<?php

namespace App\Foundation\Auth\Access\Response;

class ResponseBuilder
{
    public static function deny(): DenyResponse
    {
        return new DenyResponse();
    }
}
