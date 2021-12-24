<?php

namespace App\Repositories\System\Exceptions;

use Exception;

class SettingException extends Exception
{
    public static function undefinedSettingKey(string $key): self
    {
        return new static("Undefined setting key '$key' provided.");
    }
}