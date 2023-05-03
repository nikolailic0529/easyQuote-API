<?php

namespace App\Domain\Pipeliner\Integration\Models;

/**
 * @property BulkUpdateResultMap[] $created
 * @property string[]              $updated
 * @property array[]               $errors
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
