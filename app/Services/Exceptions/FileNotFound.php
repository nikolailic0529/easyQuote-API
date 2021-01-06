<?php

namespace App\Services\Exceptions;

use Exception;

class FileNotFound extends Exception
{
    public static function filePath($filePath)
    {
        return new static("File '$filePath' not found.");
    }
}
