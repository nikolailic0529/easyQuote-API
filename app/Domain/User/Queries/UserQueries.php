<?php

namespace App\Domain\User\Queries;

use App\Domain\Activity\Models\Activity;
use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\FilterFieldPipe;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\FilterRelationPipe;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;

class UserQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function userListQuery(Request $request = new Request()): Builder
    {
        $model = new User();
        /** @var Team $teamModel */
        $teamModel = $model->team()->getModel();
        $divisionModel = $teamModel->businessDivision()->getModel();

        $builder = $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->team()->getQualifiedForeignKeyName(),
                ...$model->qualifyColumns([
                    'first_name',
                    'middle_name',
                    'last_name',
                    'user_fullname',
                    'email',
                ]),
                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
            ])
            ->leftJoin(
                $teamModel->getTable(),
                $teamModel->getQualifiedKeyName(),
                $model->team()->getQualifiedForeignKeyName()
            )
            ->leftJoin(
                $divisionModel->getTable(),
                $divisionModel->getQualifiedKeyName(),
                $teamModel->businessDivision()->getQualifiedForeignKeyName(),
            );

        return RequestQueryBuilder::for(
            $builder, $request
        )
            ->addCustomBuildQueryPipe(
                new FilterFieldPipe('team_id', $model->team()->getQualifiedForeignKeyName()),
                new FilterRelationPipe(
                    'company_id',
                    "{$model->companies()->getRelationName()}.{$model->companies()->getRelatedKeyName()}"
                ),
                new FilterFieldPipe('business_division_id', $divisionModel->getQualifiedKeyName()),
                new class() implements RequestQueryBuilderPipe {
                    public function __invoke(BuildQueryParameters $parameters): void
                    {
                        [$request, $builder] = [$parameters->getRequest(), $parameters->getBuilder()];

                        $builder->where(static function (Builder $builder) use ($request): void {
                            if ($request->has('active')) {
                                $builder->where('is_active', $request->boolean('active'));
                            }
                        });
                    }
                }
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function paginateUsersQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new User();
        $countryModel = $model->country()->getModel();
        $activityModel = new Activity();

        /** @var \Staudenmeir\LaravelCte\Query\Builder&Builder $query */
        $query = $model->newQuery();

        $latestUserLoginsTable = 'latest_user_logins';
        $query->withExpression('latest_user_logins',
            $activityModel->newQuery()
                ->select([
                    $activityModel->causer()->getQualifiedForeignKeyName(),
                ])
                ->selectRaw(
                    'max('.$activityModel->getQualifiedCreatedAtColumn().') as last_login_at'
                )
                ->where('description', 'authenticated')
                ->groupBy($activityModel->causer()->getQualifiedForeignKeyName())
                ->toBase()
        );

        $query->select([
            $model->qualifyColumn('*'),
            "{$countryModel->qualifyColumn('name')} as country_name",
            "{$countryModel->qualifyColumn('iso_3166_2')} as country_code",
            "$latestUserLoginsTable.last_login_at",
            'teams.team_name as team_name',
            'roles.name as role_name',
        ])
            ->with(['roles', 'salesUnits', 'image'])
            ->leftJoin($countryModel->getTable(), $countryModel->getQualifiedKeyName(), $model->country()->getQualifiedForeignKeyName())
            ->leftJoin($latestUserLoginsTable, "$latestUserLoginsTable.causer_id", $model->getQualifiedKeyName())
            ->leftJoin('teams', static function (JoinClause $join): void {
                $join->on('teams.id', 'users.team_id');
            })
            ->leftJoin('roles', static function (JoinClause $join): void {
                $join->where('roles.id', static function (BaseBuilder $builder): void {
                    $builder->select('role_id')
                        ->from('model_has_roles')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->limit(1);
                });
            })
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(
                'created_at',
                'email',
                'name',
                'first_name',
                'last_name',
                'role',
                'team_name',
                'country_name',
                'country_code',
                'language',
                'last_login_at',
            )
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
                name: $model->qualifyColumn('user_fullname'),
                email: $model->qualifyColumn('email'),
                first_name: $model->qualifyColumn('first_name'),
                last_name: $model->qualifyColumn('last_name'),
                country_name: $countryModel->qualifyColumn('name'),
                country_code: $countryModel->qualifyColumn('iso_3166_2'),
                last_login_at: "$latestUserLoginsTable.last_login_at",
                role: 'roles.name',
                team_name: 'teams.team_name',
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
