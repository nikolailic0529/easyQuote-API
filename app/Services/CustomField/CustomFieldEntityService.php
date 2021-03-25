<?php

namespace App\Services\CustomField;

use App\DTO\CustomField\UpdateCustomFieldValueCollection;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class CustomFieldEntityService
{
    protected ConnectionInterface $connection;

    protected ValidatorInterface $validator;

    public function __construct(ConnectionInterface $connection, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->connection = $connection;
    }

    public function updateValuesOfCustomField(CustomField $customField, UpdateCustomFieldValueCollection $collection): void
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
                    $fieldValue->{$fieldValue->getUpdatedAtColumn()} = $fieldValue->freshTimestampString();
                    $fieldValue->exists = true;
                });
            } else {
                $fieldValueModels[] = tap(new CustomFieldValue(), function (CustomFieldValue $fieldValue) use ($customField, $value, $entityOrder) {
                    $fieldValue->{$fieldValue->getKeyName()} = (string)Uuid::generate(4);
                    $fieldValue->customField()->associate($customField);
                    $fieldValue->field_value = $value->field_value;
                    $fieldValue->is_default = $value->is_default;
                    $fieldValue->entity_order = $entityOrder;
                    $fieldValue->{$fieldValue->getCreatedAtColumn()} = $fieldValue->freshTimestampString();
                    $fieldValue->{$fieldValue->getUpdatedAtColumn()} = $fieldValue->freshTimestampString();
                });
            }

            $entityOrder++;
        }

        $this->connection->transaction(function () use ($customField, $fieldValueModels, $existingModelKeys) {
            $customField->values()
                ->whereKeyNot($existingModelKeys)
                ->delete();

            foreach ($fieldValueModels as $model) {
                $model->saveQuietly();
            }
        });
    }
}
