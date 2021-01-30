<?php

namespace App\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserQueries
{
    public function usersListQuery(): Builder
    {
        return User::query()
            ->select([
                'id', 'first_name', 'last_name', 'email'
            ]);
    }
}
