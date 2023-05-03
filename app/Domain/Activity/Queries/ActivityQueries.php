<?php

namespace App\Domain\Activity\Queries;

use App\Domain\Activity\Models\Activity;
use App\Domain\Activity\Queries\Filters\FilterActivityByCauser;
use App\Domain\Activity\Queries\Filters\FilterActivityByCustomPeriodPipe;
use App\Domain\Activity\Queries\Filters\FilterActivityByDefinedPeriod;
use App\Domain\Activity\Queries\Filters\FilterActivityByDescriptionPipe;
use App\Domain\Activity\Queries\Filters\FilterActivityBySubjectTypesPipe;
use App\Domain\Address\Models\Addressable;
use App\Domain\Appointment\Models\ModelHasAppointments;
use App\Domain\Contact\Models\Contactable;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Task\Models\ModelHasTasks;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
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
                new Expression('COALESCE(users.user_fullname, oauth_clients.name) as causer_name'),
                "{$model->qualifyColumn('causer_service')} as causer_service_name",
                "{$model->qualifyColumn('properties')} as properties",
                "{$model->getQualifiedCreatedAtColumn()} as created_at",
            ])
            ->leftJoin('users', static function (JoinClause $join) use ($model): void {
                $join->on('users.id', $model->causer()->getQualifiedForeignKeyName());
            })
            ->leftJoin('oauth_clients', static function (JoinClause $join) use ($model): void {
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
        $modelHasAddresses = new Addressable();
        $modelHasContacts = new Contactable();

        /** @var \Staudenmeir\LaravelCte\Query\Builder&Builder $query */
        $query = $model->newQuery();

        $relatedCte = [];

        $query->withExpression($relatedCte[] = 'subject_has_tasks',
            $modelHasTasks
                ->newQuery()
                ->select("{$modelHasTasks->task()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasTasks->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query->withExpression($relatedCte[] = 'subject_has_appointments',
            $modelHasAppointments
                ->newQuery()
                ->select("{$modelHasAppointments->appointment()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasAppointments->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query->withExpression($relatedCte[] = 'subject_has_notes',
            $modelHasNotes
                ->newQuery()
                ->select("{$modelHasNotes->note()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasNotes->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query->withExpression($relatedCte[] = 'subject_has_addresses',
            $modelHasAddresses
                ->newQuery()
                ->select("{$modelHasAddresses->address()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasAddresses->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query->withExpression($relatedCte[] = 'subject_has_contacts',
            $modelHasContacts
                ->newQuery()
                ->select("{$modelHasContacts->contact()->getQualifiedForeignKeyName()} as related_subject_id")
                ->where($modelHasContacts->related()->getQualifiedForeignKeyName(), $subject)
                ->toBase()
        );

        $query = $query
            ->select([
                "{$model->getQualifiedKeyName()} as id",
                "{$model->subject()->getForeignKeyName()} as subject_id",
                "{$model->subject()->getMorphType()} as subject_type",
                "{$model->qualifyColumn('description')} as description",
                'users.user_fullname as causer_name',
                "{$model->qualifyColumn('causer_service')} as causer_service_name",
                "{$model->qualifyColumn('properties')} as properties",
                "{$model->getQualifiedCreatedAtColumn()} as created_at",
            ])
            ->leftJoin('users', static function (JoinClause $join) use ($model): void {
                $join->on('users.id', $model->causer()->getQualifiedForeignKeyName());
            })
            ->where(static function (Builder $builder) use ($subject, $model, $relatedCte): void {
                $builder->where($model->subject()->getQualifiedForeignKeyName(), $subject);

                foreach ($relatedCte as $cte) {
                    $builder->orWhereIn($model->subject()->getQualifiedForeignKeyName(),
                        static function (BaseBuilder $builder) use ($cte): void {
                            $builder
                                ->select('related_subject_id')
                                ->from($cte);
                        });
                }
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
