<?php

namespace App\Queries;

use App\Models\BusinessDivision;
use App\Models\SalesUnit;
use App\Models\Team;
use App\Models\User;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class TeamQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function listingQuery(): Builder
    {
        return Team::query()
            ->select([
                'id',
                'team_name',
            ])
            ->orderBy('team_name');
    }

    public function paginateTeamsQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $model = new Team;

        $query = $model->query()
            ->select([
                ...$model->qualifyColumns([
                    $model->getKeyName(),
                    'team_name',
                    'monthly_goal_amount',
                    'is_system',
                    $model->getCreatedAtColumn(),
                ]),
                "{$model->businessDivision()->qualifyColumn('division_name')} as business_division_name"
            ])
            ->leftJoin(
                table: $model->businessDivision()->getRelated()->getTable(),
                first: $model->businessDivision()->getQualifiedOwnerKeyName(),
                operator: $model->businessDivision()->getQualifiedForeignKeyName(),
            )
            ->with([
                'salesUnits' => static function (Relation $relation): void {
                    $model = new SalesUnit();

                    $relation->select(
                        $model->qualifyColumns([
                            $model->getKeyName(),
                            'unit_name',
                        ]),
                    );
                },
                'teamLeaders' => static function (Relation $relation): void {
                    $model = new User();

                    $relation->select(
                        $model->qualifyColumns([
                            $model->getKeyName(),
                            'user_fullname',
                            'email',
                        ]),
                    );
                }
            ]);

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(
                'created_at',
                'team_name',
                'business_division_name',
                'monthly_goal_amount'
            )
            ->qualifyOrderFields(
                created_at: $model->getQualifiedCreatedAtColumn(),
                team_name: $model->qualifyColumn('team_name'),
                monthly_goal_amount: $model->qualifyColumn('monthly_goal_amount'),
                business_division_name: $model->businessDivision()->qualifyColumn('division_name'),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
