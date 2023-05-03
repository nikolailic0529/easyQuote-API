<?php

namespace App\Domain\Pipeliner\Services\Exceptions;

class MultiplePipelinerEntitiesFoundException extends PipelinerException
{
    public static function opportunity(string $opportunityName, string $salesUnitName): static
    {
        return new static("Multiple opportunities found with name [$opportunityName], sales unit [$salesUnitName].");
    }
}
