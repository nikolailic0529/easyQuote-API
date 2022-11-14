<?php

namespace App\Queries;

use App\Http\Query\{Activity\FilterActivityByCauser,
    Activity\FilterActivityByCustomPeriodPipe,
    Activity\FilterActivityByDefinedPeriod,
    Activity\FilterActivityByDescriptionPipe,
    Activity\FilterActivityBySubjectTypesPipe};
use App\Models\Appointment\ModelHasAppointments;
use App\Models\ModelHasTasks;
use App\Models\Note\ModelHasNotes;
use App\Models\System\Activity;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class ActivityQueries
{
    public function __construct(
        protected Config $config,
        protected Elasticsearch $elasticsearch
    ) {
    }

    public function paginateActivitiesQuery(Request $request = null): Builder
    {
        $request ??= new Request();

        $model = (new Activity());

        $query = Activity::query()
            ->select([
                "{$model->getQualifiedKeyName()} as id",
                "{$model->subject()->getForeignKeyName()} as subject_id",
                "{$model->subject()->getMorphType()} as subject_type",
                "{$model->qualifyColumn('description')} as description",
                new Expression("COALESCE(users.user_fullname, oauth_clients.name) as causer_name"),
                "{$model->qualifyColumn('causer_service')} as causer_service_name",
                "{$model->qualifyColumn('properties')} as properties",
                "{$model->getQualifiedCreatedAtColumn()} as created_at",
            ])
            ->leftJoin('users', function (JoinClause $join) use ($model) {
                $join->on('users.id', $model->causer()->getQualifiedForeignKeyName());
            })
            ->leftJoin('oauth_clients', function (JoinClause $join) use ($model) {
                $join->on('oauth_clients.id', $model->causer()->getQualifiedForeignKeyName());
            });

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
            ])
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->addCustomBuildQueryPipe(...[
                new FilterActivityByDescriptionPipe(),
                new FilterActivityByCustomPeriodPipe(),
                new FilterActivityByDefinedPeriod(),
                new FilterActivityByCauser(),
                new FilterActivityBySubjectTypesPipe(
                    $this->config->get('activitylog.subject_types', [])
                ),
            ])
            ->process();
    }

    public function paginateActivitiesOfSubjectQuery(string $subject, Request $request = null): Builder
    {
        $request ??= new Request();

        $model = (new Activity());
        $modelHasTasks = new ModelHasTasks();
        $modelHasAppointments = new ModelHasAppointments();
        $modelHasNotes = new ModelHasNotes();

        /** @var \Staudenmeir\LaravelCte\Query\Builder&Builder $query */
        $query = $model->newQuery();

        $query->withExpression('subject_has_tasks',
            $modelHasTasks
                ->newQuery()
                ->select("{$modelHasTasks->task()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasTasks->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query->withExpression('subject_has_appointments',
            $modelHasAppointments
                ->newQuery()
                ->select("{$modelHasAppointments->appointment()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasAppointments->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query->withExpression('subject_has_notes',
            $modelHasNotes
                ->newQuery()
                ->select("{$modelHasNotes->note()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasNotes->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query = $query
            ->select([
                "{$model->getQualifiedKeyName()} as id",
                "{$model->subject()->getForeignKeyName()} as subject_id",
                "{$model->subject()->getMorphType()} as subject_type",
                "{$model->qualifyColumn('description')} as description",
                "users.user_fullname as causer_name",
                "{$model->qualifyColumn('causer_service')} as causer_service_name",
                "{$model->qualifyColumn('properties')} as properties",
                "{$model->getQualifiedCreatedAtColumn()} as created_at",
            ])
            ->leftJoin('users', function (JoinClause $join) use ($model) {
                $join->on('users.id', $model->causer()->getQualifiedForeignKeyName());
            })
            ->where(static function (Builder $builder) use ($modelHasTasks, $subject, $model) {
                $builder->where($model->subject()->getQualifiedForeignKeyName(), $subject);

                $builder->orWhereIn($model->subject()->getQualifiedForeignKeyName(),
                    static function (BaseBuilder $builder) use ($modelHasTasks): void {
                        $builder
                            ->select('related_subject_id')
                            ->from('subject_has_tasks');
                    });

                $builder->orWhereIn($model->subject()->getQualifiedForeignKeyName(),
                    static function (BaseBuilder $builder) use ($modelHasTasks): void {
                        $builder
                            ->select('related_subject_id')
                            ->from('subject_has_appointments');
                    });

                $builder->orWhereIn($model->subject()->getQualifiedForeignKeyName(),
                    static function (BaseBuilder $builder) use ($modelHasTasks): void {
                        $builder
                            ->select('related_subject_id')
                            ->from('subject_has_notes');
                    });
            });

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch),
            )
            ->allowOrderFields(...[
                'created_at',
            ])
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->addCustomBuildQueryPipe(...[
                new FilterActivityByDescriptionPipe(),
                new FilterActivityByCustomPeriodPipe(),
                new FilterActivityByDefinedPeriod(),
                new FilterActivityByCauser(),
                new FilterActivityBySubjectTypesPipe(
                    $this->config->get('activitylog.subject_types', [])
                ),
            ])
            ->process();
    }
}
