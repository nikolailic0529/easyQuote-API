<?php

namespace App\Domain\Authorization\Repositories\Exceptions;

class PrivilegeNotFoundException extends \Exception
{
    public static function level(string $level): static
    {
        return new static("Privilege [$level] not found.");
    }
}
