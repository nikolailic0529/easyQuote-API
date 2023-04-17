<?php

namespace App\Domain\Asset\Queries\Scopes;

use App\Domain\Asset\Models\Asset;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;

final class AssetScope
{
    private function __construct(
        protected readonly Request $request,
        protected readonly Gate $gate,
    ) {
    }

    public static function from(Request $request, Gate $gate): AssetScope
    {
        return new AssetScope($request, $gate);
    }

    /**
     * @throws AuthorizationException
     */
    public function __invoke(Builder $builder): void
    {
        /** @var Asset $model */
        $model = $builder->getModel();

        if (!$model instanceof Asset) {
            throw new \LogicException(sprintf('This scope is intended for [%s] model. Given [%s].', Asset::class, get_debug_type($model)));
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

        $pivot = (new ModelHasSharingUsers());

        $builder->withExpression('user_has_shared_models',
            $user->sharedModelRelations()->getQuery()
                ->select($pivot->related()->getForeignKeyName())
                ->where($pivot->related()->getMorphType(), $model->getMorphClass())
                ->toBase()
        );

        $builder->where(static function (Builder $builder) use ($user, $model): void {
            $builder->where($model->user()->getQualifiedForeignKeyName(), $user->getKey());

            // when user has access to the entities where editor rights are granted
            $builder->orWhereIn($model->getQualifiedKeyName(),
                static function (BaseBuilder $builder): void {
                    $pivot = (new ModelHasSharingUsers());
                    $builder->select($pivot->related()->getForeignKeyName())
                        ->from('user_has_shared_models');
                });
        });
    }
}
