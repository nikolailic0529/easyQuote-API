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

    public function customFieldValuesByFieldNameQuery(string $customFieldName): Builder
    {
        $customFieldModel = new CustomField();
        $customFieldValueModel = new CustomFieldValue();

        return $customFieldValueModel->newQuery()
            ->join($customFieldModel->getTable(), function (JoinClause $join) use ($customFieldName, $customFieldValueModel, $customFieldModel) {
                $join->on($customFieldModel->getQualifiedKeyName(), $customFieldValueModel->customField()->getQualifiedForeignKeyName())
                    ->where($customFieldModel->qualifyColumn('field_name'), $customFieldName)
                    ->where($customFieldModel->qualifyColumn('is_not_deleted'), true);
            })
            ->select([
                $customFieldValueModel->qualifyColumn('id'),
                $customFieldValueModel->qualifyColumn('field_value'),
                $customFieldValueModel->qualifyColumn('entity_order'),
                $customFieldValueModel->qualifyColumn('is_default'),
            ])
            ->withCasts([
                'is_default' => 'bool'
            ])
            ->orderByDesc($customFieldValueModel->qualifyColumn('is_default'))
            ->orderBy($customFieldValueModel->qualifyColumn('entity_order'));
    }
}
