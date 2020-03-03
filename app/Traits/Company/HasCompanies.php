<?php

namespace App\Traits\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCompanies
{
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
