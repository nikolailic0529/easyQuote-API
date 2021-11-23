<?php

namespace App\Services\Formatter\Exceptions;

class FormatterResolvingException extends \RuntimeException
{
    public static function unsupportedFormatter(string $formatter): static
    {
        return new static("Unsupported formatter: `$formatter`.");
    }
}