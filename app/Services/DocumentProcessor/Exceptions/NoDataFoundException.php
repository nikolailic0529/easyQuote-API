<?php

namespace App\Services\DocumentProcessor\Exceptions;

use Exception;

class NoDataFoundException extends Exception
{
    public static function noDataFoundInFile(string $fileName): self
    {
        return new static(sprintf("No data found in the '%s' file.", $fileName));
    }
}
