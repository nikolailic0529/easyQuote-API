<?php

namespace App\Queries\Scopes;

use App\Contracts\HasSalesUnit;
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

                if ($model instanceof HasSalesUnit) {
                    $builder->withExpression('user_has_sales_units',
                        $user->salesUnits()->getQuery()
                            ->select($user->salesUnits()->getModel()->getQualifiedKeyName())
                            ->toBase()
                    );
                }

                $builder->where(static function (Builder $builder) use ($user): void {
                    $builder
                        ->where($builder->qualifyColumn('user_id'), $user->getKey())
                        ->orWhereIn($builder->qualifyColumn('user_id'),
                            static function (BaseBuilder $builder) use ($user): void {
                                $builder->select($user->ledTeamUsers()->getModel()->getKeyName())
                                    ->from('led_team_users');
                            });
                })
                    ->when($model instanceof HasSalesUnit, static function (Builder $builder) use ($user): void {
                        /** @var Model&HasSalesUnit $model */
                        $model = $builder->getModel();
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
                        } elseif ($relation instanceof HasOneDeep) {
                            $through = collect($relation->getThroughParents())
                                ->zip($relation->getForeignKeys(), $relation->getLocalKeys());

                            // drop the last related (unit model)
                            $unitThrough = $through->pop();
                            $foreignKey = $unitThrough[2];

                            $table = $through->reduce(
                                static function (string $parentTable, Collection $through) use ($builder): string {
                                    [$currentModel, $foreignKey, $localKey] = $through;

                                    $currentTable = sha1($currentModel->getTable().rand());

                                    $builder->leftJoin(
                                        "{$currentModel->getTable()} as $currentTable",
                                        "$currentTable.$foreignKey",
                                        "$parentTable.$localKey"
                                    );

                                    return $currentTable;
                                }, $builder->getModel()->getTable());
                        } elseif ($relation instanceof BelongsTo) {
                            $table = $relation->getParent()->getTable();
                            $foreignKey = $relation->getForeignKeyName();
                        } else {
                            throw new \Exception("Unsupported sales unit relation.");
                        }

                        $builder->whereIn("$table.$foreignKey",
                            static function (BaseBuilder $builder) use ($user): void {
                                $builder->select($user->salesUnits()->getModel()->getKeyName())
                                    ->from('user_has_sales_units');
                            });
                    });
            });
    }
}