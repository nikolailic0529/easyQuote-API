<?php

namespace App\Traits\Quote;

use App\Models\Quote\Contract;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasContract
{
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class)->withDefault();
    }

    public function getHasContractAttribute(): bool
    {
        return $this->contract->exists;
    }

    public function getContractSubmittedAttribute(): bool
    {
        return !is_null($this->contract->submitted_at);
    }
}
