<?php

namespace App\Domain\Company\Events;

use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class CompanyCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Company $company,
        public readonly ?Model $causer = null
    ) {
    }
}
