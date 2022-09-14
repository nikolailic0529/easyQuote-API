<?php

namespace App\Integrations\Pipeliner\Models;

use DateTimeImmutable;

/**
 * @property DataEntity[] $dataSet
 */
class FieldEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $entityName,
        public readonly string $apiName,
        public readonly string $name,
        public readonly ?string $columnName,
        public readonly ?string $parentDataSetId,
        public readonly DateTimeImmutable $created,
        public readonly DateTimeImmutable $modified,
        public readonly ?string $dataSetId = null,
        public readonly ?array $dataSet = null
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            entityName: $array['entityName'],
            apiName: $array['apiName'],
            name: $array['name'],
            columnName: $array['columnName'],
            parentDataSetId: $array['parentDataSetId'],
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            dataSetId: $array['dataSetId'],
            dataSet: static::dataSetFromArray($array['dataSet'] ?? null)
        );
    }

    private static function dataSetFromArray(?array $array): ?array
    {
        if (is_null($array)) {
            return null;
        }

        return array_map(static fn(array $entity
        ): DataEntity => DataEntity::fromArray($entity['entity'] + ['allowedBy' => $entity['allowedBy']]), $array);
    }
}