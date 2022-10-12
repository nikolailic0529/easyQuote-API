<?php

namespace App\Builders;

use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Builder;

class OpportunityBuilder extends Builder
{
    public function whereSyncProtected(bool $value = true): static
    {
        $operator = $value ? '=' : '!=';
        $column = $this->qualifyColumn('flags');
        $bits = Opportunity::SYNC_PROTECTED;

        $this->whereRaw("$column & $bits $operator $bits");

        return $this;
    }

    public function whereSyncNotProtected(): static
    {
        return $this->whereSyncProtected(false);
    }
}