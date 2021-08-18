<?php

namespace App\Models;

use App\Enum\CompanyType;
use Illuminate\Database\Eloquent\Builder;

class InternalCompany extends Company
{
    protected $table = 'companies';
    
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('internalType', fn (Builder $builder) => $builder->where('type', CompanyType::INTERNAL));
    }
}
