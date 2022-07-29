<?php

namespace App\Builders;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class CompanyBuilder extends Builder
{
    public function whereSystem(bool $value = true): static
    {
        $operator = $value ? '=' : '!=';
        $column = $this->qualifyColumn('flags');
        $bits = Company::SYSTEM;

        $this->whereRaw("$column & $bits $operator $bits");

        return $this;
    }

    public function whereNonSystem(): static
    {
        return $this->whereSystem(false);
    }
}