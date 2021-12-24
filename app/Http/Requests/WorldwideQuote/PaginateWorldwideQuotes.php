<?php

namespace App\Http\Requests\WorldwideQuote;

use App\Models\Opportunity;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class PaginateWorldwideQuotes extends FormRequest
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

    public function transformWorldwideQuotesQuery(Builder $builder): Builder
    {
        return tap($builder, function (Builder $builder) {

            /** @var \App\Models\User $user */
            $user = $this->user();

            $gate = $this->container[Gate::class];

            $canViewAnyOwnerEntities = $gate->allows('viewAnyOwnerEntities', WorldwideQuote::class);

            if ($canViewAnyOwnerEntities) {
                return;
            }

            $builder->where(function (Builder $builder) use ($user) {
                $ledTeamUsersQuery = $user->ledTeamUsers()->getQuery();

                $builder
                    ->where($builder->qualifyColumn('user_id'), $user->getKey())
                    ->orWhereIn($builder->qualifyColumn('user_id'), $ledTeamUsersQuery->select($ledTeamUsersQuery->qualifyColumn('id'))->toBase());
            });



        });
    }
}
