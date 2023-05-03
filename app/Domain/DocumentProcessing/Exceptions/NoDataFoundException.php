<?php

namespace App\Domain\DocumentProcessing\Exceptions;

class NoDataFoundException extends \Exception
{
    public static function noDataFoundInFile(string $fileName): self
    {
        return new static(sprintf("No data found in the '%s' file.", $fileName));
    }
}
