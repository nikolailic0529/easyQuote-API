<?php

namespace App\Domain\Authorization\Repositories\Exceptions;

class ModuleNotFoundException extends \Exception
{
    public static function name(string $name): static
    {
        return new static("Module [$name] not found.");
    }
}
