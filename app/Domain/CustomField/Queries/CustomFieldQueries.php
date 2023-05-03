<?php

namespace App\Domain\CustomField\Queries;

use App\Domain\CustomField\Models\CustomField;
use App\Domain\CustomField\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Builder;

class CustomFieldQueries
{
    public function customFieldsListingQuery(): Builder
    {
        return CustomField::query()
            ->select([
                'id', 'field_name',
            ]);
    }

    public function listValuesOfCustomFieldQuery(CustomField $customField): Builder
    {
        $customFieldValueModel = new CustomFieldValue();

        return $customField->values()->getQuery()
            ->select([
                $customFieldValueModel->qualifyColumn('id'),
                $customFieldValueModel->qualifyColumn('field_value'),
                $customFieldValueModel->qualifyColumn('entity_order'),
                $customFieldValueModel->qualifyColumn('is_default'),
            ])
            ->with('allowedBy')
            ->withCasts([
                'is_default' => 'bool',
            ])
            ->orderByDesc($customFieldValueModel->qualifyColumn('is_default'))
            ->orderBy($customFieldValueModel->qualifyColumn('entity_order'));
    }
}
