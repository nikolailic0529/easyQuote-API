<?php

namespace App\Domain\CustomField\Services;

use App\Domain\CustomField\DataTransferObjects\UpdateCustomFieldValueCollection;
use App\Domain\CustomField\Events\CustomFieldValuesUpdated;
use App\Domain\CustomField\Models\CustomField;
use App\Domain\CustomField\Models\CustomFieldValue;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class CustomFieldEntityService
{
    public function __construct(protected ConnectionResolverInterface $connection,
                                protected EventDispatcher $eventDispatcher,
                                protected ValidatorInterface $validator)
    {
    }

    public function updateValuesOfCustomField(CustomField $customField, UpdateCustomFieldValueCollection $collection): Collection
    {
        foreach ($collection as $value) {
            $violations = $this->validator->validate($value);

            if (count($violations)) {
                throw new ValidationFailedException($value, $violations);
            }
        }

        $collection->rewind();

        $entityOrder = 0;
        /** @var CustomFieldValue[] $fieldValueModels */
        $fieldValueModels = [];
        $existingModelKeys = [];

        foreach ($collection as $value) {
            if (!is_null($value->entity_id)) {
                $existingModelKeys[] = $value->entity_id;

                $fieldValueModels[] = tap(new CustomFieldValue(), function (CustomFieldValue $fieldValue) use ($value, $entityOrder) {
                    $fieldValue->{$fieldValue->getKeyName()} = $value->entity_id;
                    $fieldValue->field_value = $value->field_value;
                    $fieldValue->is_default = $value->is_default;
                    $fieldValue->entity_order = $entityOrder;
                    $fieldValue->setRelation('allowedBy', CustomFieldValue::query()->findMany($value->allowed_by));
                    $fieldValue->{$fieldValue->getUpdatedAtColumn()} = $fieldValue->freshTimestampString();
                    $fieldValue->exists = true;
                });
            } else {
                $fieldValueModels[] = tap(new CustomFieldValue(), function (CustomFieldValue $fieldValue) use ($customField, $value, $entityOrder) {
                    $fieldValue->{$fieldValue->getKeyName()} = (string) Uuid::generate(4);
                    $fieldValue->customField()->associate($customField);
                    $fieldValue->field_value = $value->field_value;
                    $fieldValue->is_default = $value->is_default;
                    $fieldValue->entity_order = $entityOrder;
                    $fieldValue->setRelation('allowedBy', CustomFieldValue::query()->findMany($value->allowed_by));
                    $fieldValue->{$fieldValue->getCreatedAtColumn()} = $fieldValue->freshTimestampString();
                    $fieldValue->{$fieldValue->getUpdatedAtColumn()} = $fieldValue->freshTimestampString();
                });
            }

            ++$entityOrder;
        }

        $this->connection->connection()->transaction(static function () use ($customField, $fieldValueModels, $existingModelKeys): void {
            $customField->values()
                ->whereKeyNot($existingModelKeys)
                ->delete();

            foreach ($fieldValueModels as $model) {
                $model->saveQuietly();
                $model->allowedBy()->sync($model->allowedBy);
            }
        });

        return tap(new Collection($fieldValueModels), function () use ($customField): void {
            $this->eventDispatcher->dispatch(new CustomFieldValuesUpdated($customField));
        });
    }
}
