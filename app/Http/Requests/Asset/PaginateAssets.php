<?php

namespace App\Http\Requests\Asset;

use App\Models\Asset;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class PaginateAssets extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function transformAssetsQuery(Builder $query): Builder
    {
        return tap($query, function (Builder $builder) {

            /** @var User $user */
            $user = $this->user();

            $gate = $this->container[Gate::class];

            $canViewAnyOwnerEntities = $gate->allows('viewAnyOwnerEntities', Asset::class);

            if ($canViewAnyOwnerEntities) {
                return $builder;
            }

            return $builder->where(function (Builder $builder) use ($user) {
                $ledTeamUsersQuery = $user->ledTeamUsers()->getQuery();

                $builder
                    ->where($builder->qualifyColumn('user_id'), $user->getKey())
                    ->orWhereIn($builder->qualifyColumn('user_id'), $ledTeamUsersQuery->select($ledTeamUsersQuery->qualifyColumn('id'))->toBase());
            });

        });
    }
}
