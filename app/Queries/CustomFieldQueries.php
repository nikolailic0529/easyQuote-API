<?php

namespace App\Queries;

use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        return CustomFieldValue::query()
            ->whereHas('customField', function (Builder $relation) use ($customFieldName) {
                $relation->where('field_name', $customFieldName);
            })
            ->select([
                'id', 'field_value', 'entity_order', 'is_default'
            ])
            ->withCasts([
                'is_default' => 'bool'
            ])
            ->orderByDesc('is_default')
            ->orderBy('entity_order');
    }
}
