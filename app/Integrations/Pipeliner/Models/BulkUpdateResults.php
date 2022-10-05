<?php

namespace App\Integrations\Pipeliner\Models;

/**
 *
 * @property-read BulkUpdateResultMap[] $created
 * @property-read string[] $updated
 * @property-read array[] $errors
 */
class BulkUpdateResults
{
    public function __construct(
        public readonly array $created,
        public readonly array $updated,
        public readonly array $errors,
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            created: array_map(BulkUpdateResultMap::fromArray(...), $array['created']),
            updated: $array['updated'],
            errors: $array['errors'],
        );
    }
}