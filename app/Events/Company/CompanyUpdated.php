<?php

namespace App\Events\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class CompanyUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly Company $company,
        public readonly Company $oldCompany,
        public readonly ?Model $causer = null
    ) {
    }
}
