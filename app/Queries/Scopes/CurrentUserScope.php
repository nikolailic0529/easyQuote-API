<?php

namespace App\Queries\Scopes;

use App\Contracts\HasSalesUnit;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;

class CurrentUserScope
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
        $model = $builder->getModel();

        /** @var User|null $user */
        $user = $this->request->user();

        if (null === $user) {
            Response::deny('The query cannot be performed without user authentication.')->authorize();
        }

        $builder->unless($this->gate->allows('viewAnyOwnerEntities', $model::class),
            function (Builder $builder) use ($model, $user): void {
                /** @var \Staudenmeir\LaravelCte\Query\Builder $builder */

                $builder->withExpression('led_team_users',
                    $user->ledTeamUsers()->getQuery()
                        ->select($user->ledTeamUsers()->getModel()->getQualifiedKeyName())
                        ->toBase()
                );

                $foreignKeyForSalesUnitRelation = $model instanceof HasSalesUnit
                    ? $this->resolveQualifiedForeignKeyNameForSalesUnitRelation($builder)
                    : null;

                if ($model instanceof HasSalesUnit) {
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
                }

                $builder->where(function (Builder $builder) use ($foreignKeyForSalesUnitRelation, $user, $model): void {
                    $builder
                        // By default, we query the entities which belong to the sales units of a user and where the user
                        // is an owner or account manager
                        ->where(function (Builder $builder) use ($foreignKeyForSalesUnitRelation, $user, $model): void {
                            $builder
                                ->where(static function (Builder $builder) use ($user, $model): void {
                                    $builder->where($builder->qualifyColumn('user_id'), $user->getKey());

                                    if ($model instanceof Opportunity) {
                                        $builder->orWhere($model->accountManager()->getQualifiedForeignKeyName(),
                                            $user->getKey());
                                    }
                                })
                                ->when($model instanceof HasSalesUnit,
                                    function (Builder $builder) use ($user, $foreignKeyForSalesUnitRelation): void {
                                        $builder->whereIn($foreignKeyForSalesUnitRelation,
                                            static function (BaseBuilder $builder) use ($user): void {
                                                $builder->select($user->salesUnits()->getModel()->getKeyName())
                                                    ->from('user_has_sales_units');
                                            });
                                    });
                        })
                        // Also, we query the entities which belong to the units from the teams where the user is a leader
                        ->orWhere(function (Builder $builder) use ($foreignKeyForSalesUnitRelation, $user, $model): void {
                            $builder->when($model instanceof HasSalesUnit,
                                function (Builder $builder) use ($user, $foreignKeyForSalesUnitRelation): void {
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
        /** @var Model&HasSalesUnit $model */
        $model = $builder->getModel();

        if (!$model instanceof HasSalesUnit) {
            throw new \Exception(sprintf("Model must be an instance of [%s].", HasSalesUnit::class));
        }

        $relation = $model->salesUnit();

        // When the model belongs to sales unit through another model,
        // the parent table must be joined
        if ($relation instanceof HasOneThrough) {
            $parent = $relation->getParent();
            $table = sha1($parent->getTable().rand());
            $foreignKey = $relation->getSecondLocalKeyName();

            $builder->leftJoin(
                table: "{$parent->getTable()} as $table",
                first: "$table.{$parent->getKeyName()}",
                operator: "=",
                second: $relation->getQualifiedLocalKeyName()
            );

            return "$table.$foreignKey";
        }

        if ($relation instanceof HasOneDeep) {
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

        if ($relation instanceof BelongsTo) {
            $table = $relation->getParent()->getTable();
            $foreignKey = $relation->getForeignKeyName();

            return "$table.$foreignKey";
        }

        throw new \Exception("Unsupported sales unit relation.");
    }
}