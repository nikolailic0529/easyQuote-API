<?php

namespace App\Domain\ContractType\Queries;

use App\Domain\ContractType\Models\ContractType;
use Illuminate\Database\Eloquent\Builder;

class ContractTypeQueries
{
    public function contractTypesListQuery(): Builder
    {
        return ContractType::query();
    }
}
