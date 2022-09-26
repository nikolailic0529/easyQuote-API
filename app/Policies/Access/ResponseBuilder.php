<?php

namespace App\Policies\Access;

class ResponseBuilder
{
    public static function deny(): DenyResponse
    {
        return new DenyResponse();
    }
}