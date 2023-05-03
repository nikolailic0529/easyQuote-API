<?php

namespace App\Domain\Company\Events;

use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

final class CompanyUpdated
{
    use Dispatchable;

    const ATTRIBUTES_CHANGED = 1 << 0;
    const RELATIONS_CHANGED = 1 << 1;

    public function __construct(
        public readonly Company $company,
        public readonly Company $oldCompany,
        public readonly ?Model $causer = null,
        public readonly int $flags = 0,
    ) {
    }
}
