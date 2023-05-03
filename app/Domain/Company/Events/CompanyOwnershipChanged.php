<?php

namespace App\Domain\Company\Events;

use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class CompanyOwnershipChanged
{
    use Dispatchable;

    public bool $afterCommit = true;

    public function __construct(
        public readonly Company $company,
        public readonly Company $oldCompany,
        public readonly ?Model $causer = null
    ) {
    }
}
