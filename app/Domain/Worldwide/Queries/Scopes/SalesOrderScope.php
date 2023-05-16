<?php

namespace App\Domain\Worldwide\Queries\Scopes;

use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\SalesOrder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SalesOrderScope
{
    public function __construct(
        protected readonly Request $request,
        protected readonly Gate $gate,
    ) {
    }

    public static function from(Request $request, Gate $gate): static
    {
        return new static($request, $gate);
    }

    /**
     * @throws AuthorizationException
     */
    public function __invoke(Builder $builder): void
    {
        /** @var SalesOrder $model */
        $model = $builder->getModel();

        if (!$model instanceof SalesOrder) {
            throw new \LogicException(sprintf('This scope is intended for [%s] model. Given [%s].', SalesOrder::class, get_debug_type($model)));
        }

        /** @var User|null $user */
        $user = $this->request->user();

        if (!$user) {
            Response::deny('The query cannot be performed without user authentication.')->authorize();
        }

        $gate = $this->gate;

        if ($gate->allows('viewAll', $model::class)) {
            return;
        }

        /* @var \Staudenmeir\LaravelCte\Query\Builder $builder */

        $builder->withExpression('led_team_users',
            $user->ledTeamUsers()->getQuery()
                ->select($user->ledTeamUsers()->getModel()->getQualifiedKeyName())
                ->toBase()
        );

        $foreignKeyForSalesUnitRelation = $this->resolveQualifiedForeignKeyNameForSalesUnitRelation($builder);

        $builder->withExpression('user_has_sales_units',
            $user->salesUnits()->getQuery()
                ->select($user->salesUnits()->getModel()->getQualifiedKeyName())
                ->toBase()
        );

        $builder->withExpression(
            'user_has_sales_units_from_led_teams',
            $user->salesUnitsFromLedTeams()->getQuery()
                ->select($user->salesUnitsFromLedTeams()->getModel()->getQualifiedKeyName())
                ->toBase()
        );

        $builder->where(static function (Builder $builder) use ($gate, $foreignKeyForSalesUnitRelation, $user): void {
            $builder
                // By default, we query the entities which belong to the sales units of a user and where the user
                // is an owner or account manager
                ->where(static function (Builder $builder) use ($gate, $foreignKeyForSalesUnitRelation, $user): void {
                    $builder
                        // when user has access to own entities only
                        ->when($gate->denies('viewCurrentUnitsEntities', SalesOrder::class), static function (Builder $builder) use ($user): void {
                            $builder->where(static function (Builder $builder) use ($user): void {
                                $builder->where($builder->qualifyColumn('user_id'), $user->getKey());
                            });
                        })
                        ->where(static function (Builder $builder) use ($user, $foreignKeyForSalesUnitRelation): void {
                            $builder->whereIn($foreignKeyForSalesUnitRelation,
                                static function (BaseBuilder $builder) use ($user): void {
                                    $builder->select($user->salesUnits()->getModel()->getKeyName())
                                        ->from('user_has_sales_units');
                                })
                                // Also, we query the entities which belong to the units from the teams where the user is a leader
                                ->orWhere(static function (Builder $builder) use ($foreignKeyForSalesUnitRelation, $user): void {
                                    $builder->whereIn($foreignKeyForSalesUnitRelation,
                                        static function (BaseBuilder $builder) use ($user): void {
                                            $builder->select($user->salesUnitsFromLedTeams()->getModel()->getKeyName())
                                                ->from('user_has_sales_units_from_led_teams');
                                        });
                                });
                        });
                });
        });
    }

    protected function resolveQualifiedForeignKeyNameForSalesUnitRelation(Builder $builder): string
    {
        /** @var SalesOrder $model */
        $model = $builder->getModel();

        $relation = $model->salesUnit();

        $through = collect($relation->getThroughParents())
            ->zip($relation->getForeignKeys(), $relation->getLocalKeys());

        // drop the last related (unit model)
        $unitThrough = $through->pop();
        $foreignKey = $unitThrough[2];

        $table = $through->reduce(
            static function (string $parentTable, Collection $through) use ($builder
            ): string {
                [$currentModel, $foreignKey, $localKey] = $through;

                $currentTable = sha1($currentModel->getTable().rand());

                $builder->leftJoin(
                    "{$currentModel->getTable()} as $currentTable",
                    "$currentTable.$foreignKey",
                    "$parentTable.$localKey"
                );

                return $currentTable;
            }, $builder->getModel()->getTable());

        return "$table.$foreignKey";
    }
}
