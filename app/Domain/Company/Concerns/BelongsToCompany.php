<?php

namespace App\Domain\Company\Concerns;

use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withDefault();
    }
}
