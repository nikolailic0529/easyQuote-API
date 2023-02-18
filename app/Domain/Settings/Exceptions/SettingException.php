<?php

namespace App\Domain\Settings\Exceptions;

class SettingException extends \Exception
{
    public static function undefinedSettingKey(string $key): self
    {
        return new static("Undefined setting key '$key' provided.");
    }
}
