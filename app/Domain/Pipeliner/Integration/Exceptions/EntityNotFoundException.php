<?php

namespace App\Domain\Pipeliner\Integration\Exceptions;

class EntityNotFoundException extends \Exception implements PipelinerIntegrationException
{
    public static function notFoundById(string $entityId, string $type): static
    {
        return new static("Entity not found, entityId: `$entityId`, type: `$type`.");
    }
}
