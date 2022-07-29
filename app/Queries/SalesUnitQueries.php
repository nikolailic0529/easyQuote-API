<?php

namespace App\Queries;

use App\Models\SalesUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SalesUnitQueries
{
    public function listSalesUnitsQuery(Request $request = new Request()): Builder
    {
        $model = new SalesUnit();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                ...$model->qualifyColumns([
                    'unit_name',
                    'is_default',
                    'is_enabled',
                ]),
                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
            ])
            ->orderByDesc('is_default')
            ->orderBy('entity_order');
    }
}