<?php

namespace App\Domain\SalesUnit\Queries;

use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use Devengine\RequestQueryBuilder\Contracts\RequestQueryBuilderPipe;
use Devengine\RequestQueryBuilder\Models\BuildQueryParameters;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;

class SalesUnitQueries
{
    public function listSalesUnitsQuery(Request $request = new Request()): Builder
    {
        $model = new SalesUnit();

        $builder = $model->newQuery()
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

        return RequestQueryBuilder::for(
            $builder,
            $request
        )
            ->addCustomBuildQueryPipe(new class() implements RequestQueryBuilderPipe {
                public function __invoke(BuildQueryParameters $parameters): void
                {
                    $request = $parameters->getRequest();
                    $builder = $parameters->getBuilder();
                    /** @var User $user */
                    $user = $request->user();

                    if ($user->hasRole(R_SUPER)) {
                        return;
                    }

                    /* @var \Staudenmeir\LaravelCte\Query\Builder $builder */

                    $builder->withExpression('user_has_sales_units',
                        $user->salesUnits()->getQuery()
                            ->select($user->salesUnits()->getModel()->getQualifiedKeyName())
                            ->toBase()
                    );

                    $builder->withExpression('user_has_sales_units_from_led_teams',
                        $user->salesUnitsFromLedTeams()->getQuery()
                            ->select($user->salesUnitsFromLedTeams()->getModel()->getQualifiedKeyName())
                            ->toBase()
                    );

                    if ($request->has('filter.assigned_to_me')) {
                        $builder->where(static function (Builder $builder) use ($request, $user): void {
                            $builder->whereIn((new SalesUnit())->getQualifiedKeyName(),
                                static function (BaseBuilder $builder) use ($user): void {
                                    $builder->select($user->salesUnits()->getModel()->getKeyName())
                                        ->from('user_has_sales_units')
                                        ->union(static function (BaseBuilder $builder) use ($user): void {
                                            $builder->select($user->salesUnitsFromLedTeams()->getModel()->getKeyName())
                                                ->from('user_has_sales_units_from_led_teams');
                                        });
                                }, not: !$request->boolean('filter.assigned_to_me'));
                        });
                    }
                }
            })
            ->process();
    }
}
