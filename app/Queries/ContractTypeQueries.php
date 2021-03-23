<?php

namespace App\Queries;

use App\Models\ContractType;
use Illuminate\Database\Eloquent\Builder;

class ContractTypeQueries
{
    public function contractTypesListQuery(): Builder
    {
        return ContractType::query();
    }
}
