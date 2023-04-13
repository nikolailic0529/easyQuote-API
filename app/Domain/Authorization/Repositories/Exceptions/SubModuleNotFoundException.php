<?php

namespace App\Domain\Authorization\Repositories\Exceptions;

class SubModuleNotFoundException extends \Exception
{
    public static function name(string $name): static
    {
        return new static("SubModule [$name] not found.");
    }
}
