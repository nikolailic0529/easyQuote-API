<?php

namespace App\Integrations\Pipeliner\Exceptions;

class EntityNotFoundException extends \Exception implements PipelinerIntegrationException
{
    public static function notFoundById(string $entityId, string $type): static
    {
        return new static("Entity not found, entityId: `$entityId`, type: `$type`.");
    }
}