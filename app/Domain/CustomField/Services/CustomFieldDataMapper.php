<?php

namespace App\Domain\CustomField\Services;

use App\Domain\CustomField\Models\CustomField;
use App\Domain\CustomField\Models\CustomFieldValue;
use App\Domain\Pipeliner\Integration\Models\DataEntity;
use App\Domain\Pipeliner\Integration\Models\FieldDataSetItem;
use App\Domain\Pipeliner\Integration\Models\FieldEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CustomFieldDataMapper
{
    public function mapCustomFieldFromFieldEntity(FieldEntity $entity, string $fieldName): CustomField
    {
        return tap(new CustomField(), function (CustomField $field) use ($entity, $fieldName): void {
            $field->pl_reference = $entity->id;
            $field->field_name = $fieldName;

            $field->setRelation('values', $this->mapCustomFieldValuesFromFieldEntityDataSet($entity->dataSet));
        });
    }

    /**
     * @param FieldDataSetItem[] $dataSet
     */
    public function mapCustomFieldValuesFromFieldEntityDataSet(array $dataSet): Collection
    {
        return Collection::wrap($dataSet)->map(function (DataEntity $item, int $n) {
            return tap(new CustomFieldValue(), static function (CustomFieldValue $value) use ($item, $n): void {
                $value->pl_reference = $item->id;
                $value->field_value = $item->optionName;
                $value->calc_value = $item->calcValue;
                $value->entity_order = $n;

                if (is_array($item->allowedBy)) {
                    $value->setRelation('allowedBy', CustomFieldValue::query()->whereIn('pl_reference', $item->allowedBy)->get());
                }
            });
        });
    }

    public function mergeAttributesFrom(CustomField $field, CustomField $another): void
    {
        $toBeMergedAttributes = [
            'pl_reference',
        ];

        foreach ($toBeMergedAttributes as $attribute) {
            if (null !== $another->$attribute) {
                $field->$attribute = $another->$attribute;
            }
        }

        $toBeMergedOneToManyRelations = [
            'values',
        ];

        foreach ($toBeMergedOneToManyRelations as $relation) {
            $field->$relation->each(static function (Model $model) {
                $model->{$model->getDeletedAtColumn()} = $model->freshTimestamp();
            });

            $field->$relation->push(
                ...$another->$relation
            );
        }
    }
}
