<?php

namespace App\Queries;

use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;

class CustomFieldQueries
{
    public function customFieldsListingQuery(): Builder
    {
        return CustomField::query()
            ->select([
                'id', 'field_name'
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
                'is_default' => 'bool'
            ])
            ->orderByDesc($customFieldValueModel->qualifyColumn('is_default'))
            ->orderBy($customFieldValueModel->qualifyColumn('entity_order'));
    }
}
