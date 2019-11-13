<?php

namespace App\Traits\Company;

use App\Models\Company;

trait HasCompanies
{
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
