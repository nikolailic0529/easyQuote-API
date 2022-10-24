<?php

namespace App\Queries;

use App\Models\Pipeliner\PipelinerSyncError;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PipelinerSyncErrorQueries
{
    public function baseSyncErrorsQuery(Request $request = new Request()): Builder
    {
        $model = new PipelinerSyncError();

        $query = $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->entity()->getQualifiedForeignKeyName(),
                ...$model->qualifyColumns([
                    $model->entity()->getMorphType(),
                    'error_message'
                ]),
                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
            ])
            ->whereNull($model->qualifyColumn('archived_at'))
            ->whereNull($model->qualifyColumn('resolved_at'))
            ->with('entity');

        return RequestQueryBuilder::for($query, $request)
            ->allowOrderFields(
                'created_at',
                'updated_at'
            )
            ->allowQuickSearchFields(
                'error_message'
            )
            ->process();
    }
}