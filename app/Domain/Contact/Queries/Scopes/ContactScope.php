<?php

namespace App\Domain\Contact\Queries\Scopes;

use App\Domain\Contact\Models\Contact;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;

final class ContactScope
{
    private function __construct(
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
        /** @var Contact $model */
        $model = $builder->getModel();

        if (!$model instanceof Contact) {
            throw new \LogicException(sprintf('This scope is intended for [%s] model. Given [%s].', Contact::class, get_debug_type($model)));
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

        $foreignKeyForSalesUnitRelation = $this->resolveQualifiedForeignKeyNameForSalesUnitRelation($model);

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

        $pivot = (new ModelHasSharingUsers());

        $builder->withExpression('user_has_shared_models',
            $user->sharedModelRelations()->getQuery()
                ->select($pivot->related()->getForeignKeyName())
                ->where($pivot->related()->getMorphType(), $model->getMorphClass())
                ->toBase()
        );

        $builder->where(static function (Builder $builder) use ($gate, $foreignKeyForSalesUnitRelation, $user, $model): void {
            $builder
                // By default, we query the entities which belong to the sales units of a user and where the user
                // is an owner
                ->where(static function (Builder $builder) use ($gate, $foreignKeyForSalesUnitRelation, $user, $model): void {
                    $builder
                        ->when($gate->denies('viewCurrentUnitsEntities', $model::class),
                            static function (Builder $builder) use ($user): void {
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
                                ->orWhereIn($foreignKeyForSalesUnitRelation,
                                    static function (BaseBuilder $builder) use ($user): void {
                                        $builder->select($user->salesUnitsFromLedTeams()->getModel()->getKeyName())
                                            ->from('user_has_sales_units_from_led_teams');
                                    });
                        });
                });
        });
    }

    private function resolveQualifiedForeignKeyNameForSalesUnitRelation(Contact $model): string
    {
        $relation = $model->salesUnit();

        $table = $relation->getParent()->getTable();
        $foreignKey = $relation->getForeignKeyName();

        return "$table.$foreignKey";
    }
}
