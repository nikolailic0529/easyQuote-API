<?php

namespace App\Services\Exceptions;

use Exception;

class FileException extends Exception
{
    public static function notFound(string $filePath): self
    {
        return new static("File '$filePath' not found.");
    }

    public static function notReadable(string $filePath): self
    {
        return new static("File '$filePath' is not readable.");
    }
}
