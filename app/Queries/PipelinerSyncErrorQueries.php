<?php

namespace App\Queries;

use App\Models\Pipeliner\PipelinerSyncError;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
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
                $model->qualifyColumn('archived_at'),
                $model->qualifyColumn('resolved_at'),
            ])
            ->has('entity')
            ->with('entity');

        return RequestQueryBuilder::for($query, $request)
            ->allowOrderFields(
                'created_at',
                'updated_at',
                'resolved_at',
                'archived_at',
            )
            ->allowQuickSearchFields(
                'error_message'
            )
            ->addCustomBuildQueryPipe(
                new class implements RequestQueryBuilderPipe {
                    public function __invoke(BuildQueryParameters $parameters): void
                    {
                        [$builder, $request] = [$parameters->getBuilder(), $parameters->getRequest()];

                        $builder->where(static function (Builder $builder) use ($request): void {
                            if ($request->boolean('only_archived')) {
                                $builder->whereNotNull($builder->qualifyColumn('archived_at'));
                            } else {
                                $builder->whereNull($builder->qualifyColumn('archived_at'));
                            }

                            if ($request->boolean('only_resolved')) {
                                $builder->whereNotNull($builder->qualifyColumn('resolved_at'));
                            } else {
                                $builder->whereNull($builder->qualifyColumn('resolved_at'));
                            }
                        });
                    }
                },
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}