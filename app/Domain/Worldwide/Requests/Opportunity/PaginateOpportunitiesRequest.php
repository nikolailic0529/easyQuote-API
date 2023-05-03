<?php

namespace App\Domain\Worldwide\Requests\Opportunity;

use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class PaginateOpportunitiesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    public function transformOpportunitiesQuery(Builder $builder): Builder
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = $this->user();

        $gate = $this->container[Gate::class];

        $canViewAnyOwnerEntities = $gate->allows('viewAnyOwnerEntities', Opportunity::class);

        if ($canViewAnyOwnerEntities) {
            return $builder;
        }

        return $builder->where(function (Builder $builder) use ($user) {
            $ledTeamUsersQuery = $user->ledTeamUsers()->getQuery();

            $builder
                ->where($builder->qualifyColumn('user_id'), $user->getKey())
                ->orWhere($builder->qualifyColumn('account_manager_id'), $user->getKey())
                ->orWhereIn($builder->qualifyColumn('account_manager_id'), $ledTeamUsersQuery->select($ledTeamUsersQuery->qualifyColumn('id'))->toBase())
                ->orWhereIn($builder->qualifyColumn('user_id'), $ledTeamUsersQuery->select($ledTeamUsersQuery->qualifyColumn('id'))->toBase());
        });
    }
}
