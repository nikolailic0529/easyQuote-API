<?php

namespace App\Domain\Note\Services;

use http\Exception\RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection as BaseCollection;

class UnifiedNoteDataMapper
{
    const ENTITY_TYPE_ATTR = 'note_entity_type';

    protected array $entityBuilders = [];

    public function mapUnifiedNoteCollection(BaseCollection $collection): BaseCollection
    {
        $hydrated = [];

        foreach ($collection as $item) {
            $hydrated[] = $this->hydrateUnifiedNoteFromArray((array) $item);
        }

        return new BaseCollection($hydrated);
    }

    protected function hydrateUnifiedNoteFromArray(array $data): Model
    {
        return $this->resolveBuilderForEntityType($data[self::ENTITY_TYPE_ATTR])($data);
    }

    protected function resolveBuilderForEntityType(string $entityType)
    {
        return $this->entityBuilders[$entityType] ??= value(function () use ($entityType) {
            $class = Relation::getMorphedModel($entityType) ?? $entityType;

            if (false === class_exists($class)) {
                throw new RuntimeException('Unsupported entity type provided.');
            }

            /** @var Model $model */
            $model = new $class();

            return function (array $attributes) use ($model): Model {
                return $model->newFromBuilder($attributes);
            };
        });
    }
}
